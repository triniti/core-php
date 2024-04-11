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


class InspectSeoHandler implements CommandHandler
{
    private Ncr $ncr;
    private Key $key;
    private int $retryCount = 0;

    protected Flags $flags;

    const MAX_RETRIES = 3;
    const RETRY_DELAY = "+5 minutes";
    const SITE_URL = 'site_url';

    public static function handlesCuries(): array
    {
        return [
            'triniti:sys:command:inspect-seo',
        ];
    }

    public function __construct(Ncr $ncr, Key $key, Flags $flags)
    {
        $this->ncr = $ncr;
        $this->key = $key;
        $this->flags = $flags;
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
            error_log("An error occurred in checkIndexStatus. Exception: {$e->getMessage()}");
            error_log("Node ID: " . $node->get('node_id') . " | URL: {$url}");
            error_log("Retry Count: {$this->retryCount}");

            $this->handleIndexingFailure($command, $pbjx, false, function () {
                error_log("Final failure after retries.");
            });

            return;
        }

        if (!$urlStatus->getInspectionResult() instanceof UrlInspectionResult) {
            return;
        }

        $webVerdict = $urlStatus->getInspectionResult()->getIndexStatusResult()->getVerdict();
        $indexingState = $urlStatus->getInspectionResult()->getIndexStatusResult()->getIndexingState();
        $ampVerdict = $urlStatus->getInspectionResult()->getAmpResult()?->getVerdict();

        if ($node->get('is_unlisted') === true && $webVerdict === "PASS") {
            $this->handleIndexingFailure($command, $pbjx, false, function (){
                error_log("Page is marked as unlisted but has passed indexing checks, this is a failure case.");
            });
        }

        if ($node->get('amp_enabled') === true && ($webVerdict !== "PASS" || $ampVerdict !== "PASS") && !in_array($indexingState, $successStates)) {
            $this->handleIndexingFailure($command, $pbjx, true);
            return;
        }

        if ($node->get('amp_enabled') === false && in_array($indexingState, $successStates) && $webVerdict === "PASS") {
            $this->handleIndexingFailure($command, $pbjx, false, function (){
                return error_log("AMP is disabled and article has passed indexing checks, this is a failure case.");
            });
        }

        if ($webVerdict === "PASS" && in_array($indexingState, $successStates)) {
            $this->handleIndexingSuccess();
        }


        $this->handleIndexingFailure($command, $pbjx, true);
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


    public function handleIndexingSuccess(?callable $successCallback): void {
        if (is_callable($successCallback)) {
            $successCallback();
        }
    }

    public function handleIndexingFailure(Message $command, Pbjx $pbjx, bool $shouldRetry, ?callable $failureCallback = null): void {
        if ($shouldRetry && $this->retryCount < self::MAX_RETRIES) {
            $this->retryCount++;
            $retryCommand = clone $command;
            $retryCommand->set('search_engines', ['google']);
            $pbjx->sendAt($retryCommand, strtotime(self::RETRY_DELAY));
        } else {
            if (is_callable($failureCallback)) {
                $failureCallback();
            }
        }
    }
}
