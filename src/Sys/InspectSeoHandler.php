<?php
declare(strict_types=1);

namespace Triniti\Sys;

use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;
use Gdbots\Ncr\Event\NodeProjectedEvent;
use Gdbots\Ncr\Ncr;
use Gdbots\Pbj\Message;
use Gdbots\Pbj\WellKnown\NodeRef;
use Gdbots\Pbjx\CommandHandler;
use Gdbots\Pbjx\Pbjx;
use Gdbots\UriTemplate\UriTemplateService;
use Google\Service\SearchConsole\InspectUrlIndexResponse;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Triniti\Schemas\Sys\Event\SeoInspectedV1;


class InspectSeoHandler implements CommandHandler
{
    private Ncr $ncr;
    private Key $key;
    private Flags $flags;
    protected Pbjx $pbjx;
    private InspectUrlIndexResponse $inspectSeoUrlIndexResponse;
    private bool $isIndexed;
    protected LoggerInterface $logger;

    const INSPECT_SEO_HANDLER_GOOGLE_SITE_URL_FLAG_NAME = 'inspect_seo_handler_google_site_url';
    const INSPECT_SEO_MAX_TRIES_FLAG_NAME = 'inspect_seo_max_tries';
    const INSPECT_SEO_RETRY_DELAY_FLAG_NAME = 'inspect_seo_retry_delay';

    public static function handlesCuries(): array
    {
        return [
            'triniti:sys:command:inspect-seo',
        ];
    }

    public function __construct(Ncr $ncr, Key $key, Flags $flags, Pbjx $pbjx, InspectUrlIndexResponse $inspectSeoUrlIndexResponse = null, bool $isIndexed = false, ?LoggerInterface $logger = null)
    {
        $this->ncr = $ncr;
        $this->key = $key;
        $this->flags = $flags;
        $this->pbjx = $pbjx;
        $this->inspectSeoUrlIndexResponse = $inspectSeoUrlIndexResponse;
        $this->isIndexed = $isIndexed;
        $this->logger = $logger ?: new NullLogger();
    }

    public function handleCommand(Message $command, Pbjx $pbjx): void
    {
        $retryCommand = clone $command;
        $retryCommand->set('ctx_retries', $command->get('ctx_retries', 0) + 1);
        $searchEngines = $retryCommand->get('search_engines', ['google']);
        $retryCommand->clear('search_engines');

        foreach ($searchEngines as $searchEngine) {
            $command->addToSet('search_engines', [$searchEngine]);
        }

        $enginesToRemove = [];
        $nodeRef = $command->get('node_ref');
        $node = $this->ncr->getNode($nodeRef);

        foreach ($searchEngines as $searchEngine) {
            $methodName = 'checkIndexStatusFor' . ucfirst($searchEngine);

            if (!method_exists($this, $methodName)) {
                $enginesToRemove[] = $searchEngine;
                continue;
            }

            $this->$methodName($command, $node, $searchEngine);

            if ($this->getIsIndexed()) {
                $enginesToRemove[] = $searchEngine;
                $this->handleIndexingSuccess();
            }
        }

        foreach ($enginesToRemove as $searchEngine) {
            $command->removeFromSet('search_engines', [$searchEngine]);
        }

        if (!empty($command->get('search_engines'))) {
            $searchEngine = $command->get('search_engines')[0];
            $this->handleRetry($command, $node, $pbjx, $searchEngine);
        }
    }

    public function checkIndexStatusForGoogle(Message $command, Message $node, string $searchEngine = "google"): void {
        $url = UriTemplateService::expand(
            "{$node::schema()->getQName()}.canonical", $node->getUriTemplateVars()
        );

        try {
            $this->getUrlIndexResponseForGoogle($url);
        } catch (\Throwable $e) {
            $errorMessage = "An error occurred in checkIndexStatus for {$searchEngine}. Exception: {$e->getMessage()} " .
                " | Node ID: " . $node->get('node_id') .
                " | URL: {$url}" .
                " | Retry Count: {$command->get('ctx_retries')}" .
                " | Stack Track: {$e->getTraceAsString()}";

            $this->logger->error($errorMessage);
        }

        $nodeRef = $command->get('node_ref');
        $this->triggerSeoInspectedWatcher($nodeRef, $this->getIndexStatusResponse(), $searchEngine);
    }

    public function setIsIndexed(bool $indexed): void {
        $this->isIndexed = $indexed;
    }

    public function getIsIndexed(): bool {
        return $this->isIndexed;
    }

    public function setIndexStatusResponse($response): void {
        $this->inspectSeoUrlIndexResponse = $response;
    }

    public function getIndexStatusResponse(): InspectUrlIndexResponse
    {
        return $this->inspectSeoUrlIndexResponse;
    }

    public function getUrlIndexResponseForGoogle(String $url): void {
        $request = new \Google_Service_SearchConsole_InspectUrlIndexRequest();
        $request->setSiteUrl($this->flags->getString(self::INSPECT_SEO_HANDLER_GOOGLE_SITE_URL_FLAG_NAME));
        $request->setInspectionUrl($url);
        $client = new \Google_Client();
        $client->setAuthConfig(json_decode(base64_decode(Crypto::decrypt(getenv('GOOGLE_SEARCH_CONSOLE_API_SERVICE_ACCOUNT_OAUTH_CONFIG'), $this->key)), true));
        $client->addScope(\Google_Service_SearchConsole::WEBMASTERS_READONLY);
        $service = new \Google_Service_SearchConsole($client);
        $response = $service->urlInspection_index->inspect($request);

        $this->setIndexStatusResponse($response);
    }

    public function triggerSeoInspectedWatcher(NodeRef $nodeRef, InspectUrlIndexResponse $inspectUrlIndexResponse, string $searchEngine): void {
        $event = SeoInspectedV1::create();
        $node = $this->ncr->getNode($nodeRef);

        $event->set('node_ref', $nodeRef);
        $event->set('inspection_response', json_encode($inspectUrlIndexResponse));
        $event->set('search_engine', $searchEngine);

        if (!$event->has('inspection_response')) {
            return;
        }

        try {
            $seoInspectedWatcher = new SeoInspectedWatcher($this->ncr);
            $indexed = $seoInspectedWatcher->onSeoInspected(new NodeProjectedEvent($node, $event));

            $this->setIsIndexed($indexed);
        } catch (\Throwable $e){
            $this->logger->error($e);
        }
    }

    public function handleIndexingSuccess(): void {}

    public function handleIndexingFailure(Message $command, Message $node, mixed $inspectSeoUrlIndexResponse, string $searchEngine, bool $hasExceededMaxTries = false): void {
        $this->triggerSeoInspectedWatcher($node->generateNodeRef(), $inspectSeoUrlIndexResponse, $searchEngine);

        if ($hasExceededMaxTries) {
            $this->logger->error("Final failure after retries.");
        }
    }

    public function handleRetry(Message $command, Message $node, Pbjx $pbjx, string $searchEngine): void {
        $maxRetries = $this->flags->getInt(self::INSPECT_SEO_MAX_TRIES_FLAG_NAME);
        $retries = $command->get('ctx_retries', 0);

        if ($retries <= $maxRetries){
            $retryCommand = clone $command;
            $retryCommand->set('ctx_retries', 1 + $retryCommand->get('ctx_retries'));

            if (getenv('APP_ENV') === 'prod') {
                $pbjx->sendAt($retryCommand, strtotime($this->flags->getString(self::INSPECT_SEO_RETRY_DELAY_FLAG_NAME )));
            } else {
                $pbjx->send($retryCommand);
            }
        } else {
            $this->handleIndexingFailure($command, $node, $this->getIndexStatusResponse(),  $searchEngine, true);
        }
    }
}
