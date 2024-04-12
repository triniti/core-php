<?php
declare(strict_types=1);

namespace Triniti\Sys;

use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;
use Gdbots\Ncr\Ncr;
use Gdbots\Pbj\Message;
use Gdbots\Pbjx\CommandHandler;
use Gdbots\Pbjx\Pbjx;
use Gdbots\UriTemplate\UriTemplateService;
use Google\Service\SearchConsole\InspectUrlIndexResponse;
use Google\Service\SearchConsole\UrlInspectionResult;
use Psr\Log\LoggerInterface;


class InspectSeoHandler implements CommandHandler
{
    private Ncr $ncr;
    private Key $key;
    private LoggerInterface $logger;
    private int $retryCount = 0;

    protected Flags $flags;

    const MAX_RETRIES = 3;
    const RETRY_DELAY = "+5 minutes";
    const SITE_URL = 'site_url';
    const FAILED_RETRY_MESSAGE = "Final failure after retries.";

    public static function handlesCuries(): array
    {
        return [
            'triniti:sys:command:inspect-seo',
        ];
    }

    public function __construct(Ncr $ncr, Key $key, Flags $flags, LoggerInterface $logger)
    {
        $this->ncr = $ncr;
        $this->key = $key;
        $this->flags = $flags;
        $this->logger = $logger;
    }

    public function handleCommand(Message $command, Pbjx $pbjx): void
    {
        $searchEngines = $command->get('search_engines', []);
        if (!empty($searchEngines) && !in_array('google', $searchEngines)) {
            return;
        }

        $nodeRef = $command->get('node_ref');
        $article = $this->ncr->getNode($nodeRef);

        $this->checkIndexStatus($command, $article, $pbjx);
    }


    public function checkIndexStatus(Message $command, Message $node, Pbjx $pbjx): void {
        $successStates = ["INDEXING_ALLOWED", "SUCCESSFUL"];

        $url = UriTemplateService::expand(
            "{$node::schema()->getQName()}.canonical", $node->getUriTemplateVars()
        );

        try {
            $urlStatus = $this->getUrlIndexResponse($url);
        } catch (\Throwable $e) {
            $this->logger->error("An error occurred in checkIndexStatus. Exception: {$e->getMessage()}");
            $this->logger->error("Node ID: " . $node->get('node_id') . " | URL: {$url}");
            $this->logger->error("Retry Count: {$this->retryCount}");

            $this->handleIndexingFailure($command, $pbjx, true, function () {
                $this->logger->error(self::FAILED_RETRY_MESSAGE);
            });

            return;
        }

        if (!$urlStatus->getInspectionResult() instanceof UrlInspectionResult) {
            return;
        }

        $webVerdict = $urlStatus->getInspectionResult()->getIndexStatusResult()->getVerdict();
        $ampVerdict = $urlStatus->getInspectionResult()->getAmpResult()?->getVerdict();
        $indexingState = $urlStatus->getInspectionResult()->getIndexStatusResult()->getIndexingState();

        $webPassed = $webVerdict === "PASS" && in_array($indexingState, $successStates);
        $ampDisabledPassed = $node->get('amp_enabled') === false && $webPassed;
        $isUnlistedPassed = $node->get('is_unlisted') === true && $webVerdict === "PASS";
        $ampEnabledFailed = $node->get('amp_enabled') === true && ($webVerdict !== "PASS" || $ampVerdict !== "PASS") && !in_array($indexingState, $successStates);

        if ($isUnlistedPassed) {
            $this->handleIndexingFailure($command, $pbjx, false, function (){
                $this->logger->logger("FAIL - Page is marked as unlisted but has passed indexing check.");
            });
        }

        if ($ampDisabledPassed) {
            $this->handleIndexingFailure($command, $pbjx, false, function (){
                $this->logger->error("FAIL - AMP is disabled and article has passed indexing check.");
            });
        }

        if ($ampEnabledFailed) {
            $this->handleIndexingFailure($command, $pbjx, true, function () {
                $this->logger->error(self::FAILED_RETRY_MESSAGE);
            });
        }

        if ($webPassed) {
            $this->handleIndexingSuccess();
        }

        $this->handleIndexingFailure($command, $pbjx, true, function () {
            $this->logger->error(self::FAILED_RETRY_MESSAGE);
        });
    }

    public function getUrlIndexResponse(String $url): InspectUrlIndexResponse {
        $request = new \Google_Service_SearchConsole_InspectUrlIndexRequest();
        $request->setSiteUrl($this->flags->getString(self::SITE_URL));
        $request->setInspectionUrl($url);

        $client = new \Google_Client();
        $client->setAuthConfig(json_decode(base64_decode(Crypto::decrypt(getenv('GOOGLE_SEARCH_CONSOLE_API_SERVICE_ACCOUNT_OAUTH_CONFIG'), $this->key)), true));
        $client->addScope(\Google_Service_SearchConsole::WEBMASTERS_READONLY);

        $service = new \Google_Service_SearchConsole($client);

        return $service->urlInspection_index->inspect($request);
    }


    public function handleIndexingSuccess(?callable $successCallback): bool {
        if (is_callable($successCallback)) {
            $successCallback();
        }

        return true;
    }

    public function handleIndexingFailure(Message $command, Pbjx $pbjx, bool $shouldRetry, ?callable $failureCallback = null, ?callable $apiCallback = null): bool {
        if ($shouldRetry && $this->retryCount < self::MAX_RETRIES) {
            $this->retryCount++;
            $retryCommand = clone $command;
            $searchEngines = $retryCommand->get('search_engines');
            $retryCommand->set('search_engines', [$searchEngines]);
            $pbjx->sendAt($retryCommand, strtotime(self::RETRY_DELAY));
        } elseif (is_callable($failureCallback)) {
            $failureCallback();
        }

        if (is_callable($apiCallback)) {
            $apiCallback();
        }

        return false;
    }
}
