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
    protected LoggerInterface $logger;
    protected Flags $flags;

    const INSPECT_SEO_URL_SITE_URL_FLAG_NAME = 'inspect_seo_site_url';
    const MAX_TRIES_FLAG_NAME = 'max_tries';
    const FAILED_RETRY_MESSAGE = "Final failure after retries.";
    const UNLISTED_FAIL_MESSAGE = "FAIL - Page is marked as unlisted but has passed indexing check.";
    const AMP_FAIL_MESSAGE = "FAIL - AMP is disabled and article has passed indexing check.";

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

        if (empty($searchEngines)) {
            return;
        }

        $nodeRef = $command->get('node_ref');
        $article = $this->ncr->getNode($nodeRef);

        if ($searchEngines[0] === "google") {
            $this->checkIndexStatusForGoogle($command, $article, $pbjx);
        }
    }


    public function checkIndexStatusForGoogle(Message $command, Message $article, Pbjx $pbjx): void {
        $successStates = ["INDEXING_ALLOWED", "SUCCESSFUL"];
        $retries = $command->get('ctx_retries');

        $url = UriTemplateService::expand(
            "{$article::schema()->getQName()}.canonical", $article->getUriTemplateVars()
        );

        try {
            $urlStatus = $this->getUrlIndexResponse($url);
        } catch (\Throwable $e) {
            $this->logger->error("An error occurred in checkIndexStatus. Exception: {$e->getMessage()}");
            $this->logger->error("Article ID: " . $article->get('node_id') . " | URL: {$url}");
            $this->logger->error("Retry Count: {$retries}");

            $this->handleIndexingFailure(
                $command,
                $pbjx,
                $article,
                true,
                null,
                self::FAILED_RETRY_MESSAGE
            );

            return;
        }

        if (!$urlStatus->getInspectionResult() instanceof UrlInspectionResult) {
            return;
        }

        $inspectSeoResult = $urlStatus->getInspectionResult();
        $webVerdict = $inspectSeoResult->getIndexStatusResult()->getVerdict();
        $ampVerdict = $inspectSeoResult->getAmpResult()?->getVerdict();
        $indexingState = $inspectSeoResult->getIndexStatusResult()->getIndexingState();

        $webPassed = $webVerdict === "PASS" && in_array($indexingState, $successStates);
        $ampDisabledPassed = !$article->get('amp_enabled') && $webPassed;
        $isUnlistedPassed = $article->get('is_unlisted') && $webVerdict === "PASS";
        $ampEnabledFailed = $article->get('amp_enabled') && ($webVerdict !== "PASS" || $ampVerdict !== "PASS") && !in_array($indexingState, $successStates);

        if ($ampEnabledFailed || $webVerdict === "FAIL") {
            $this->handleIndexingFailure(
                $command,
                $pbjx,
                $article,
                true,
                $inspectSeoResult,
                self::FAILED_RETRY_MESSAGE
            );

            return;
        }

        if ($isUnlistedPassed) {
            $this->handleIndexingFailure(
                $command,
                $pbjx,
                $article,
                true,
                $inspectSeoResult,
                self::UNLISTED_FAIL_MESSAGE
            );

            return;
        }

        if ($ampDisabledPassed) {
            $this->handleIndexingFailure(
                $command,
                $pbjx,
                $article,
                true,
                $inspectSeoResult,
                self::AMP_FAIL_MESSAGE
            );

            return;
        }

        if ($webPassed) {
            $this->handleIndexingSuccess();
            return;
        }

        $this->handleIndexingFailure(
            $command,
            $pbjx,
            $article,
            true,
            $inspectSeoResult,
            self::FAILED_RETRY_MESSAGE
        );
    }

    public function getUrlIndexResponse(String $url): InspectUrlIndexResponse {
        $request = new \Google_Service_SearchConsole_InspectUrlIndexRequest();
        $request->setSiteUrl($this->flags->getString(self::INSPECT_SEO_URL_SITE_URL_FLAG_NAME));
        $request->setInspectionUrl($url);

        $client = new \Google_Client();
        $client->setAuthConfig(json_decode(base64_decode(Crypto::decrypt(getenv('GOOGLE_SEARCH_CONSOLE_API_SERVICE_ACCOUNT_OAUTH_CONFIG'), $this->key)), true));
        $client->addScope(\Google_Service_SearchConsole::WEBMASTERS_READONLY);

        $service = new \Google_Service_SearchConsole($client);

        return $service->urlInspection_index->inspect($request);
    }


    public function handleIndexingSuccess(?callable $successCallback): void {
        if (is_callable($successCallback)) {
            $successCallback();
        }
    }

    public function handleIndexingFailure(Message $command, Pbjx $pbjx, Message $article, bool $shouldRetry, InspectUrlIndexResponse $inspectSeoUrlIndexResponse, string $failMessage = ''): void {
        $isRetryEnabled = $this->flags->getBoolean('retry_enabled');
        
        if ($isRetryEnabled) {
            $retries = $command->get('ctx_retries');
            $maxRetries = $this->flags->getInt('max_tries');
    
            if ($shouldRetry && $retries < $maxRetries) {
                $retryCommand = clone $command;
                $searchEngines = $retryCommand->get('search_engines');
    
                $retryCommand->set('search_engines', [$searchEngines]);
                $pbjx->sendAt($retryCommand, strtotime("+5 minutes"));
            } 
        }

        if (!empty($failMessage)) {
            $this->logger->error($failMessage); 
        }
    }
}
