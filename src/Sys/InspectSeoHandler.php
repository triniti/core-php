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
use function PHPUnit\Framework\callback;


class InspectSeoHandler implements CommandHandler
{
    private string $siteUrl;
    private Ncr $ncr;
    private Key $key;
    private $retryCount = 0;

    const MAX_RETRIES = 3;
    const RETRY_DELAY = 60;

    public static function handlesCuries(): array
    {
        return [
            'triniti:sys:command:inspect-seo',
        ];
    }

    public function __construct(Ncr $ncr, Key $key, string $siteUrl)
    {
        $this->ncr = $ncr;
        $this->siteUrl = $siteUrl;
        $this->key = $key;

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

    private function canRetry(): bool
    {
        return $this->retryCount < self::MAX_RETRIES;
    }

    public function checkIndexStatus(Message $command, Message $node, Pbjx $pbjx): void {
        $successStates = ["INDEXING_ALLOWED", "SUCCESSFUL"];

        $url = UriTemplateService::expand(
            "{$node::schema()->getQName()}.canonical", $node->getUriTemplateVars()
        );

        try {
            $urlStatus = $this->getUrlIndexResponse($url);
        } catch (\Throwable $e) {
           throw $e;
        }

        if (!$urlStatus->getInspectionResult() instanceof UrlInspectionResult) {
            return;
        }

        $webVerdict = $urlStatus->getInspectionResult()->getIndexStatusResult()->getVerdict();

        if ($urlStatus->getInspectionResult()->getAmpResult() !== null) {
            $ampVerdict = $urlStatus->getInspectionResult()->getAmpResult()->getVerdict();
        }

        $indexingState = $urlStatus->getInspectionResult()->getIndexStatusResult()->getIndexingState();

        if ($node->get('is_unlisted') === "true" || $node->get('amp_enabled') === "false"){
            if ($webVerdict === "PASS") {
                error_log("Page should not be indexed");
            }
        }

        if ($node->get('amp_enabled') === "true"){
            if ($webVerdict === "PASS" && $ampVerdict === "PASS" && in_array($indexingState, $successStates)) {
                $this->handleIndexingSuccess();
                return;
            }
        }

        if ($webVerdict === "PASS" && in_array($indexingState, $successStates)) {
            $this->handleIndexingSuccess();
            return;
        }

        $this->handleIndexingFailure($command, $pbjx);
    }

    public function getUrlIndexResponse(String $url): InspectUrlIndexResponse {
        $request = new \Google_Service_SearchConsole_InspectUrlIndexRequest();
        $request->setSiteUrl($this->siteUrl);
        $request->setInspectionUrl("https://www.tmz.com/2024/04/04/gypsy-rose-blanchard-holds-hands-ex-fiance-ken-smoke-break-dollar-general/");
        $client = new \Google_Client();
        $client->setAuthConfig(json_decode(base64_decode(Crypto::decrypt(getenv('GOOGLE_SEARCH_CONSOLE_API_SERVICE_ACCOUNT_OAUTH_CONFIG'), $this->key)), true));
        $client->addScope(\Google_Service_SearchConsole::WEBMASTERS_READONLY);
        $service = new \Google_Service_SearchConsole($client);

        return $service->urlInspection_index->inspect($request);
    }

    public function handleIndexingSuccess(callable $successCallback): void {
        if (!!$successCallback) {
            $successCallback();
        }
    }

    public function handleIndexingFailure(Message $command, Pbjx $pbjx, callable $failureCallback): void {
        if ($this->canRetry()) {
            $this->retryCount++;
            $retryCommand = clone $command;
            $retryCommand->set('search_engines', ['google']);
            $pbjx->sendAt($retryCommand, time() + self::RETRY_DELAY);
        }

        if (!!$failureCallback) {
            $failureCallback();
        }
    }
}
