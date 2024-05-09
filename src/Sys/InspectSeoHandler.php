<?php
declare(strict_types=1);

namespace Triniti\Sys;

use Defuse\Crypto\Crypto;
use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use Defuse\Crypto\Exception\WrongKeyOrModifiedCiphertextException;
use Defuse\Crypto\Key;
use Gdbots\Ncr\Event\NodeProjectedEvent;
use Gdbots\Ncr\Exception\GdbotsNcrException;
use Gdbots\Ncr\Ncr;
use Gdbots\Pbj\Exception\GdbotsPbjException;
use Gdbots\Pbj\Message;
use Gdbots\Pbj\WellKnown\NodeRef;
use Gdbots\Pbjx\CommandHandler;
use Gdbots\Pbjx\Exception\GdbotsPbjxException;
use Gdbots\Pbjx\Pbjx;
use Gdbots\UriTemplate\UriTemplateService;
use Google\Service\Exception;
use Google\Service\SearchConsole\InspectUrlIndexResponse;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Throwable;
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

    const INSPECT_SEO_GOOGLE_SITE_URL_FLAG_NAME = 'inspect_seo_google_site_url';
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
        $initialCommand = clone $command;
        $initialCommand->set('ctx_retries', $command->get('ctx_retries', 0));
        $searchEngines = $initialCommand->get('search_engines', ['google']);
        $initialCommand->clear('search_engines');

        foreach ($searchEngines as $searchEngine) {
            $initialCommand->addToSet('search_engines', [$searchEngine]);
        }

        $enginesToRemove = [];
        $nodeRef = $initialCommand->get('node_ref');
        $node = $this->ncr->getNode($nodeRef);

        foreach ($searchEngines as $searchEngine) {
            $methodName = 'checkIndexStatusFor' . ucfirst($searchEngine);

            if (!method_exists($this, $methodName)) {
                $enginesToRemove[] = $searchEngine;
                continue;
            }

            $this->$methodName($initialCommand, $node, $searchEngine);

            if ($this->getIsIndexed()) {
                $enginesToRemove[] = $searchEngine;
                $this->handleIndexingSuccess($initialCommand);
            }
        }

        foreach ($enginesToRemove as $searchEngine) {
            $initialCommand->removeFromSet('search_engines', [$searchEngine]);
        }

        if (!empty($initialCommand->get('search_engines'))) {
            foreach ($searchEngines as $searchEngine) {
                $this->handleRetry($initialCommand, $node, $pbjx, $searchEngine, $initialCommand->get('ctx_retries'));
            }
        }
    }

    /**
     * @throws GdbotsPbjException
     * @throws GdbotsNcrException
     */
    public function checkIndexStatusForGoogle(Message $command, Message $node, string $searchEngine = "google"): void {
        $url = UriTemplateService::expand(
            "{$node::schema()->getQName()}.canonical", $node->getUriTemplateVars()
        );

        try {
            $this->getUrlIndexResponseForGoogle($url);
        } catch (Throwable $e) {
            $errorMessage = "An error occurred in checkIndexStatus for {$searchEngine}. Exception: {$e->getMessage()} " .
                " | Node ID: " . $node->get('node_id') .
                " | URL: {$url}" .
                " | Retry Count: {$command->get('ctx_retries')}" .
                " | Stack Track: {$e->getTraceAsString()}";

            $this->logger->error($errorMessage);
        }

        $this->triggerSeoInspectedWatcher($node->generateNodeRef(), $this->getIndexStatusResponse(), $searchEngine);
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

    /**
     * @throws Exception
     * @throws \Google\Exception
     * @throws WrongKeyOrModifiedCiphertextException
     * @throws EnvironmentIsBrokenException
     */
    public function getUrlIndexResponseForGoogle(String $url): void {
        $request = new \Google_Service_SearchConsole_InspectUrlIndexRequest();
        $request->setSiteUrl($this->flags->getString(self::INSPECT_SEO_GOOGLE_SITE_URL_FLAG_NAME));
        $request->setInspectionUrl($url);
        $client = new \Google_Client();
        $client->setAuthConfig(json_decode(base64_decode(Crypto::decrypt(getenv('GOOGLE_SEARCH_CONSOLE_API_SERVICE_ACCOUNT_OAUTH_CONFIG'), $this->key)), true));
        $client->addScope(\Google_Service_SearchConsole::WEBMASTERS_READONLY);
        $service = new \Google_Service_SearchConsole($client);
        $response = $service->urlInspection_index->inspect($request);

        $this->setIndexStatusResponse($response);
    }

    /**
     * @throws GdbotsPbjException
     * @throws GdbotsNcrException
     */
    public function triggerSeoInspectedWatcher(NodeRef $nodeRef, InspectUrlIndexResponse $inspectUrlIndexResponse, string $searchEngine): void {
        $event = SeoInspectedV1::create();
        $node = $this->ncr->getNode($nodeRef);
        $indexed = null;

        $event->set('node_ref', $nodeRef);
        $event->set('inspection_response', json_encode($inspectUrlIndexResponse));
        $event->set('search_engine', $searchEngine);

        if (!$event->has('inspection_response')) {
            return;
        }

        try {
            $seoInspectedWatcher = new SeoInspectedWatcher($this->ncr);
            $indexed = $seoInspectedWatcher->onSeoInspected(new NodeProjectedEvent($node, $event));
        } catch (Throwable $e){
            $this->logger->error($e);
        }

        $this->setIsIndexed($indexed);
    }

    public function handleIndexingSuccess(Message $command): Message {
        return $command;
    }


    public function handleIndexingFailure(Message $command, Message $node, mixed $inspectSeoUrlIndexResponse, string $searchEngine): Message {
        return $command;
    }

    /**
     * @throws Throwable
     * @throws GdbotsPbjxException
     * @throws GdbotsPbjException
     * @throws GdbotsNcrException
     */
    public function handleRetry(Message $command, Message $node, Pbjx $pbjx, string $searchEngine, int $retries): void {
        $maxRetries = $this->flags->getInt(self::INSPECT_SEO_MAX_TRIES_FLAG_NAME, 5);;
        $retryCommand = clone $command;

        if ($retries < $maxRetries){
            $retryCommand->set('ctx_retries', $retryCommand->get('ctx_retries') + 1);

            if (getenv('APP_ENV') === 'prod') {
                $pbjx->sendAt($retryCommand, strtotime($this->flags->getString(self::INSPECT_SEO_RETRY_DELAY_FLAG_NAME )));
            } else {
                $pbjx->send($retryCommand);
            }
        } else {
            $this->handleIndexingFailure($command, $node, $this->getIndexStatusResponse(),  $searchEngine);
        }
    }
}
