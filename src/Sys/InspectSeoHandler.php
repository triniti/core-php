<?php
declare(strict_types=1);

namespace Triniti\Sys;

use Defuse\Crypto\Crypto;
use Gdbots\Ncr\Exception\GdbotsNcrException;
use Gdbots\Ncr\Ncr;
use Gdbots\Pbj\Exception\GdbotsPbjException;
use Gdbots\Pbj\Message;
use Gdbots\Pbjx\CommandHandler;
use Gdbots\Pbjx\Pbjx;
use Gdbots\Schemas\Pbjx\StreamId;
use Gdbots\UriTemplate\UriTemplateService;
use Google\Service\SearchConsole\InspectUrlIndexResponse;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Triniti\Schemas\Sys\Event\SeoInspectedV1;
use Defuse\Crypto\Key;


class InspectSeoHandler implements CommandHandler
{
    const INSPECT_SEO_GOOGLE_SITE_URL_FLAG_NAME = 'inspect_seo_handler_google_site_url';
    const INSPECT_SEO_DELAY_DISABLED_FLAG_NAME = "inspect_seo_delay_disabled";
    const INSPECT_SEO_MAX_TRIES_FLAG_NAME = 'inspect_seo_max_tries';
    const INSPECT_SEO_RETRY_DELAY_FLAG_NAME = 'inspect_seo_retry_delay';

    public static function handlesCuries(): array
    {
        return [
            'triniti:sys:command:inspect-seo',
        ];
    }

    public function __construct(
        private readonly Ncr $ncr,
        private readonly Key $key,
        private readonly Flags $flags,
        private readonly Pbjx $pbjx,
        private readonly LoggerInterface $logger = new NullLogger(),
      )
    {
    }

    public function handleCommand(Message $command, Pbjx $pbjx): void
    {
        $searchEngines = $command->get('search_engines', ['google', 'bing']);
        $retryCommand = clone $command;
        $retryCommand->clear('search_engines');

        try {
            $node = $this->ncr->getNode($retryCommand->get('node_ref'));
        } catch (NodeNotFound $e) {
            $this->logger->error('Unable to get node!');
            return;
        }

        foreach ($searchEngines as $searchEngine) {
            $retryCommand = $this->checkIndexStatus($retryCommand, $node, $searchEngine);
        }

        if (empty($retryCommand->get('search_engines'))) {
            return;
        }

        $retries = $retryCommand->get('ctx_retries');
        $maxRetries = $this->flags->getInt(self::INSPECT_SEO_MAX_TRIES_FLAG_NAME, 5);

        if ($retries >= $maxRetries) {
            $this->logger->error('No more retries left!');
            return;
        }
        $retryCommand->set('ctx_retries', $retries + 1);
        $pbjx->send($retryCommand);
    }


    public function checkIndexStatus(Message $command, Message $node, $searchEngine): Message {
        if (!$searchEngine) {
            $this->logger->warning('Unable to handle this search engine...');
            return $command;
        }

        $response = null;

        if ($searchEngine == "google") {
            $request = new \Google_Service_SearchConsole_InspectUrlIndexRequest();
            $request->setSiteUrl($this->flags->getString(self::INSPECT_SEO_GOOGLE_SITE_URL_FLAG_NAME));
            $url = UriTemplateService::expand(
                "{$node::schema()->getQName()}.canonical", $node->getUriTemplateVars()
            );
            $request->setInspectionUrl($url);
            $client = new \Google_Client();
            $client->addScope(\Google_Service_SearchConsole::WEBMASTERS_READONLY);

            try {
                $base64EncodedAuthConfig = Crypto::decrypt(getenv('GOOGLE_SEARCH_CONSOLE_API_SERVICE_ACCOUNT_OAUTH_CONFIG'), $this->key);
                $client->setAuthConfig(json_decode(base64_decode($base64EncodedAuthConfig), true));
                $service = new \Google_Service_SearchConsole($client);
                $response = $service->urlInspection_index->inspect($request);
            } catch (\Throwable $e) {
                $errorMessage = "An error occurred in checkIndexStatus for google. Exception: {$e->getMessage()} " .
                    " | Node ID: " . $node->get('node_id') .
                    " | URL: {$url}" .
                    " | Retry Count: {$command->get('ctx_retries')}" .
                    " | Stack Track: {$e->getTraceAsString()}";

                $this->logger->error($errorMessage);
            }
        }

        $retries = $command->get('ctx_retries');
        $maxRetries = $this->flags->getInt(self::INSPECT_SEO_MAX_TRIES_FLAG_NAME, 5);
        
        if ($this->isConclusive($response, $retries, $maxRetries, $node, $searchEngine)) {
            $this->handleInspectSeoFailure($command, $node, $response, $searchEngine);
            $this->putEvent($command, $node, $response, $searchEngine);
            return $command;
        }
        
        if ($retries >= $maxRetries) {
            $this->putEvent($command, $node, $response, $searchEngine);
        } else {
            $command->addToSet('search_engines', [$searchEngine]);
        }

        return $command;
    }


    private function isConclusive(InspectUrlIndexResponse|null $response, int $currentRetries, int $maxRetries, Message $node, string $searchEngine): bool {
        if (empty($searchEngine)  || $response == null) {
            return false;
        }

        $isConclusive = false;

        if ($searchEngine == "google"){
            $inspectionResult = $response->inspectionResult;
            $indexStatusResult = $inspectionResult->indexStatusResult;
    
            $webNotIndexed = $indexStatusResult->verdict !== 'PASS';
            $isUnlisted = $node->get('is_unlisted');
    
            $ampResult = $inspectionResult->ampResult ?? null;
            $ampEnabled = $node->has('amp_enabled') && $node->get('amp_enabled');
    
            $ampNotIndexed = $ampEnabled && ($ampResult == null || $ampResult->verdict !== 'PASS');
            $isConclusive =  ($isUnlisted && $webNotIndexed) ||  (!$isUnlisted && !$webNotIndexed) || ($ampEnabled && $ampNotIndexed) || (!$ampEnabled && $ampResult && $ampResult->verdict === 'PASS');
        }

        if ($isConclusive || ($currentRetries >= $maxRetries)) {
            return true;
        }

        return $isConclusive;
    }


    /**
     * @throws GdbotsPbjException
     * @throws GdbotsNcrException
     */
     private function putEvent(Message $command, Message $node, InspectUrlIndexResponse|null $response, string $searchEngine): void {
        $nodeRef = $node->generateNodeRef();
        $event = SeoInspectedV1::create()
            ->set('node_ref', $node->generateNodeRef())
            ->set('search_engine', $searchEngine);

        if ($response !== null) {
            $event->set('inspection_response', json_encode($response));
        }

        $streamId = StreamId::fromString(sprintf(
            '%s:%s:%s',
            $nodeRef->getVendor(),
            $nodeRef->getLabel(),
            $nodeRef->getId()
        ));

        $this->pbjx
            ->copyContext($command, $event)
            ->getEventStore()->putEvents($streamId, [$event], null, ['causator' => $command]);
    }

    public function handleInspectSeoFailure(Message $command, Message $node, mixed $response, string $searchEngine): Message {
         return $command;
    }

    public function handleInspectSeoSuccess(Message $command): Message {
         return $command;
    }
}

