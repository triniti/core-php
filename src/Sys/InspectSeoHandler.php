<?php
declare(strict_types=1);

namespace Triniti\Sys;

use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;
use Gdbots\Ncr\Ncr;
use Gdbots\Pbj\Message;
use Gdbots\Pbj\WellKnown\NodeRef;
use Gdbots\Pbjx\CommandHandler;
use Gdbots\Pbjx\Pbjx;
use Gdbots\UriTemplate\UriTemplateService;
use Google\Service\SearchConsole\InspectUrlIndexResponse;
use Google\Service\SearchConsole\UrlInspectionResult;
use Psr\Log\LoggerInterface;
use Triniti\Schemas\Sys\Event\SeoInspectedV1;


class InspectSeoHandler implements CommandHandler
{
    private Ncr $ncr;
    private Key $key;
    private Pbjx $pbjx;
    private InspectUrlIndexResponse $inspectSeoUrlIndexResponse;
    protected LoggerInterface $logger;
    protected Flags $flags;

    const INSPECT_SEO_HANDLER_GOOGLE_SITE_URL_FLAG_NAME = 'inspect_seo_handler_google_site_url';
    const MAX_TRIES_FLAG_NAME = 'inspect_seo_max_tries';
    const INSPECT_SEO_RETRY_DELAY_FLAG_NAME = 'inspect_seo_retry_delay_flag';

    public static function handlesCuries(): array
    {
        return [
            'triniti:sys:command:inspect-seo',
        ];
    }

    public function __construct(Ncr $ncr, Key $key, Flags $flags, LoggerInterface $logger, Pbjx $pbjx, InspectUrlIndexResponse $inspectSeoUrlIndexResponse)
    {
        $this->ncr = $ncr;
        $this->key = $key;
        $this->flags = $flags;
        $this->logger = $logger;
        $this->pbjx = $pbjx;
        $this->inspectSeoUrlIndexResponse = $inspectSeoUrlIndexResponse;
    }

    public function handleCommand(Message $originalCommand, Pbjx $pbjx): void
    {
        $command = clone $originalCommand;
        $command->set('ctx_retries', $command->get('ctx_retries', 0) + 1);
        $searchEngines = $command->get('search_engines', ['google']);

        $command->clear('search_engines');
        foreach ($searchEngines as $searchEngine) {
            $command->addToSet('search_engines', $searchEngine);
        }

        $enginesToRemove = [];

        foreach ($searchEngines as $searchEngine) {
            $methodName = 'checkIndexStatusFor' . ucfirst($searchEngine);

            if (!method_exists($this, $methodName)) {
                $enginesToRemove[] = $searchEngine;
                continue;
            }

            $isIndexed = $this->$methodName($command, $pbjx, $searchEngine);

            if ($isIndexed) {
                $enginesToRemove[] = $searchEngine;
                $this->handleIndexingSuccess();
            }
        }

        foreach ($enginesToRemove as $searchEngine) {
            $command->removeFromSet('search_engines', $searchEngine);
        }

        if (!empty($command->get('search_engines'))) {
            $this->handleRetry($command, $pbjx);
        }
    }

    public function checkIndexStatusForGoogle(Message $command): bool {
        $nodeRef = $command->get('node_ref');
        $node = $this->ncr->getNode($nodeRef);

        $url = UriTemplateService::expand(
            "{$node::schema()->getQName()}.canonical", $ $node->getUriTemplateVars()
        );

        try {
            $this->setIndexStatusResponse($this->getUrlIndexResponse($url));
        } catch (\Throwable $e) {
            dump($e->getTraceAsString());
            $errorMessage = "An error occurred in checkIndexStatus. Exception: {$e->getMessage()} " .
                " | Node ID: " . $node->get('node_id') .
                " | URL: {$url}" .
                " | Retry Count: {$command->get('ctx_retries')}";

            $this->logger->error($errorMessage);
        }

        $result = $this->triggerSeoInspectedWatcher($nodeRef, $this->inspectSeoUrlIndexResponse->getInspectionResult(), "google");

        return $result == "PASSED";
    }

    public function setIndexStatusResponse(InspectUrlIndexResponse $response): void {
        $this->inspectSeoUrlIndexResponse = $response;
    }

    public function getIndexStatusResponse(){
        return $this->inspectSeoUrlIndexResponse;
    }

    public function getUrlIndexResponse(String $url): InspectUrlIndexResponse {
        $request = new \Google_Service_SearchConsole_InspectUrlIndexRequest();
        $request->setSiteUrl(self::INSPECT_SEO_HANDLER_GOOGLE_SITE_URL_FLAG_NAME);
        $request->setInspectionUrl($url);
        $client = new \Google_Client();
        $client->setAuthConfig(json_decode(base64_decode(Crypto::decrypt(getenv('GOOGLE_SEARCH_CONSOLE_API_SERVICE_ACCOUNT_OAUTH_CONFIG'), $this->key)), true));
        $client->addScope(\Google_Service_SearchConsole::WEBMASTERS_READONLY);

        $service = new \Google_Service_SearchConsole($client);

        return $service->urlInspection_index->inspect($request);
    }

    public function triggerSeoInspectedWatcher(NodeRef $nodeRef, UrlInspectionResult $inspectionResult, string $searchEngine): Message {
        $seoEventInspectedCommand = SeoInspectedV1::create();
        $seoEventInspectedCommand->set('node_ref', $nodeRef);
        $seoEventInspectedCommand->set('inspection_response', $inspectionResult);
        $seoEventInspectedCommand->set('search_engine', $searchEngine);

       return $this->pbjx->request($seoEventInspectedCommand);
    }

    public function handleIndexingSuccess(): void {}

    public function handleIndexingFailure(Message $command, Message $node, InspectUrlIndexResponse $inspectSeoUrlIndexResponse): void {
        $this->logger->error("Final failure after retries.");
    }

    public function handleRetry(Message $command, Message $node, Pbjx $pbjx): void {
        $maxRetries = $this->flags->getInt('max_retries', 5);
        $retries = $command->get('ctx_retries', 0);
        $nodeRef = $command->get('node_ref');

        if ($retries <= $maxRetries){
            $retryCommand = clone $command;
            $retryCommand->set('ctx_retries', 1 + $retryCommand->get('ctx_retries'));

            if (getenv('APP_ENV') === 'prod') {
                $pbjx->sendAt($retryCommand, strtotime(self::INSPECT_SEO_RETRY_DELAY_FLAG_NAME ));
            } else {
                $pbjx->send($retryCommand);
            }
        } else {
            $this->triggerSeoInspectedWatcher($nodeRef, null,"google");
            $this->handleIndexingFailure($command, $node, $this->getIndexStatusResponse());
        }
    }
}
