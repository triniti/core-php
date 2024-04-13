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
            $this->logger->error("A search engine must be passed in.");
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
                null
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
                $inspectSeoResult
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
                "FAIL - Page is marked as unlisted but has passed indexing check."
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
                "FAIL - AMP is disabled and article has passed indexing check."
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
            $inspectSeoResult
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
        $retries = $command->get('ctx_retries');
        $maxRetries = $this->flags->getInt('max_tries');

        if ($shouldRetry) {
            if ($retries < $maxRetries){
                $retryCommand = clone $command;

                if ('prod' === getenv('APP_ENV')) {
                    $pbjx->sendAt($retryCommand, strtotime("+5 minutes"));
                } else {
                    $pbjx->send($retryCommand);
                }
               
            } else {
                $this->logger->error("Final failure after retries.");
            }
        }

        if (!empty($failMessage)) {
            $this->logger->error($failMessage);
        }
    }
}
