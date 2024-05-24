<?php
declare(strict_types=1);

namespace Triniti\Sys;

use Defuse\Crypto\Crypto;
use Gdbots\Ncr\Exception\NodeNotFound;
use Gdbots\Ncr\Ncr;
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
    public static function handlesCuries(): array
    {
        return [
            'triniti:sys:command:inspect-seo',
        ];
    }

    public function __construct(
        private readonly Ncr             $ncr,
        private readonly Key             $key,
        private readonly Flags           $flags,
        private readonly LoggerInterface $logger = new NullLogger(),
    )
    {
    }

    public function handleCommand(Message $command, Pbjx $pbjx): void
    {
        $searchEngines = $command->get('search_engines', ['google']);
        $retryCommand = clone $command;
        $retryCommand->clear('search_engines');
        $nodeRef = $retryCommand->get('node_ref');

        try {
            $node = $this->ncr->getNode($nodeRef);
        } catch (NodeNotFound $e) {
            $this->logger->error('Unable to get node for search engines processing.', [
                'exception' => $e,
                'node_ref' => $nodeRef
            ]);
            return;
        }

        foreach ($searchEngines as $searchEngine) {
            $methodName = 'checkIndexStatusFor' . ucfirst($searchEngine);

            if (!method_exists($this, $methodName)) {
                continue;
            }

            $retryCommand = $this->$methodName($retryCommand, $pbjx, $node);
        }

        if (empty($retryCommand->get('search_engines'))) {
            return;
        }

        $retries = $command->get('ctx_retries');
        $maxRetries = $this->flags->getInt('inspect_seo_max_retries', 5);

        if ($retries < $maxRetries) {
            $retryCommand->set('ctx_retries', $retryCommand->get('ctx_retries') + 1);

            $pbjx->sendAt(
                $retryCommand,
                $this->flags->getString('inspect_seo_retry_delay', "+15 minutes"),
                "{$nodeRef}.inspect-seo"
            );
        }
    }


    public function checkIndexStatusForGoogle(Message $command, Pbjx $pbjx, Message $node): Message
    {
        $request = new \Google_Service_SearchConsole_InspectUrlIndexRequest();
        $siteUrl = $this->flags->getString('inspect_seo_google_site_url', '');

        if (empty($siteUrl)) {
            return $command;
        }

        $request->setSiteUrl();
        $url = UriTemplateService::expand(
            "{$node::schema()->getQName()}.canonical", $node->getUriTemplateVars()
        );
        $request->setInspectionUrl($url);
        $client = new \Google_Client();
        $client->addScope(\Google_Service_SearchConsole::WEBMASTERS_READONLY);

        $response = null;

        try {
            $base64EncodedAuthConfig = Crypto::decrypt(getenv('GOOGLE_SEARCH_CONSOLE_API_SERVICE_ACCOUNT_OAUTH_CONFIG'), $this->key);
            $client->setAuthConfig(json_decode(base64_decode($base64EncodedAuthConfig), true));
            $service = new \Google_Service_SearchConsole($client);
            $response = $service->urlInspection_index->inspect($request);
        } catch (\Throwable $e) {
            $this->logger->error('An error occurred in checkIndexStatus for google.', [
                'exception' => $e,
                'node_ref' => $node->generateNodeRef(),
                'url' => $url,
                'retry_count' => $command->get('ctx_retries'),
                'stack_trace' => $e->getTraceAsString(),
            ]);
        }

        if ($response === null) {
            return $command;
        }

        $inspectionResult = $response->getInspectionResult();
        $indexStatusResult = $inspectionResult->getIndexStatusResult() ?? null;
        $ampResult = $inspectionResult->getAmpResult() ?? null;

        $isConclusiveForWeb = $indexStatusResult && ($indexStatusResult->getVerdict() === 'PASS' || $indexStatusResult->getVerdict() === 'FAIL');
        $isConclusiveForAmp = !$node::schema()->hasField('amp_enabled') || ($ampResult && ($ampResult->getVerdict() === 'PASS' || $ampResult->getVerdict() === 'FAIL'));

        if ($isConclusiveForWeb && $isConclusiveForAmp) {
            $this->putEvent($retryCommand, $pbjx, $node, 'google', $response);
            return $command;
        }

        $retryCommand = clone $command;
        $retries = $command->get('ctx_retries');
        $maxRetries = $this->flags->getInt('inspect_seo_max_retries', 5);
              
        if ($retries >= $maxRetries) {
            $this->logger->error('Number of retries for SEO inspection exceeded maximum.', [
                'node_ref' => $retryCommand->get('node_ref'),
                'retries' => $retries,
                'max_retries' => $maxRetries,
                'web_result_verdict' => $indexStatusResult ? $indexStatusResult->getVerdict() : 'N/A',
                'amp_result_verdict' => $ampResult ? $ampResult->getVerdict() : 'N/A',
                'is_conclusive_for_web' => $isConclusiveForWeb,
                'is_conclusive_for_amp' => $isConclusiveForAmp,
            ]);
            $this->putEvent($retryCommand, $pbjx, $node, 'google', $response);
        } else {
            $retryCommand->addToSet('search_engines', ['google']);
        }

        return $retryCommand;
    }


    private function putEvent(Message $command, Pbjx $pbjx, Message $node, string $searchEngine, ?InspectUrlIndexResponse $response = null): void
    {
        $nodeRef = $node->generateNodeRef();
        $event = SeoInspectedV1::create()
            ->set('node_ref', $node->generateNodeRef())
            ->set('search_engine', $searchEngine);

        if ($response !== null) {
            $event->set('inspection_response', json_encode($response));
        }

        $streamId = StreamId::fromNodeRef($nodeRef);

        $pbjx
            ->copyContext($command, $event)
            ->getEventStore()
            ->putEvents($streamId, [$event], null, ['causator' => $command]);
    }
}

