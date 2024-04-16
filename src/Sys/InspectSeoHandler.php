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
use Triniti\Sys\IndexSeoStatusForGoogle;


class InspectSeoHandler implements CommandHandler
{
    private Ncr $ncr;
    private Key $key;
    protected LoggerInterface $logger;
    protected Flags $flags;

    const INSPECT_SEO_URL_SITE_URL_FLAG_NAME = 'inspect_seo_site_url';
    const MAX_TRIES_FLAG_NAME = 'inspect_seo_max_tries';
    const INSPECT_SEO_DELAY_FLAG_NAME = 'inspect_seo_delay_flag';

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

        foreach ($searchEngines as $searchEngine) {
            $methodName = 'checkIndexStatusFor' . ucfirst($searchEngine);
            if (method_exists($this, $methodName)) {
                $indexStatus = $this->$methodName($command, $article, $pbjx);

                if ($indexStatus->get('success')){
                    $this->handleIndexingSuccess();
                } else {
                    $retries = $command->get('ctx_retries');
                    $maxRetries = $this->flags->getInt('max_tries');

                    if ($retries < $maxRetries){
                        $retryCommand = clone $command;
                        $retryCommand->set('ctx_retries', 1 + $retryCommand->get('ctx_retries'));

                        if ('prod' === getenv('APP_ENV')) {
                            $pbjx->sendAt($retryCommand, strtotime($this->flags->getString(self::INSPECT_SEO_DELAY_FLAG_NAME)));
                        } else {
                            $pbjx->send($retryCommand);
                        }
                    } else {
                        $this->logger->error("Final failure after retries.");
                        $this->handleIndexingFailure($command, $article, $indexStatus-get('response'));
                    }
                }
            } else {
                $this->logger->warning("Method {$methodName} does not exist for search engine {$searchEngine}.");
            }
        }
    }


    public function checkIndexStatusForGoogle(Message $command, Message $article): IndexSeoStatusForGoogle {
        $successStates = ["INDEXING_ALLOWED", "SUCCESSFUL"];
        $status = new IndexSeoStatusForGoogle();

        $url = UriTemplateService::expand(
            "{$article::schema()->getQName()}.canonical", $article->getUriTemplateVars()
        );

        try {
            $urlStatus = $this->getUrlIndexResponse($url);
        } catch (\Throwable $e) {
            $errorMessage = "An error occurred in checkIndexStatus. Exception: {$e->getMessage()}" .
                " | Article ID: " . $article->get('node_id') .
                " | URL: {$url}" .
                " | Retry Count: {$command->get('ctx_retries')}";

            $status->set('error', $errorMessage);
            $this->logger->error($errorMessage);

            return $status;
        }

        if (!$urlStatus->getInspectionResult() instanceof UrlInspectionResult) {
            return $status;
        }

        $inspectSeoResult = $urlStatus->getInspectionResult();
        $webVerdict = $inspectSeoResult->getIndexStatusResult()->getVerdict();
        $ampVerdict = $inspectSeoResult->getAmpResult()?->getVerdict();
        $indexingState = $inspectSeoResult->getIndexStatusResult()->getIndexingState();

        $webPassed = $webVerdict === "PASS" && in_array($indexingState, $successStates);
        $ampDisabledPassed = !$article->get('amp_enabled') && $webPassed;
        $isUnlistedPassed = $article->get('is_unlisted') && $webVerdict === "PASS";
        $ampEnabledFailed = $article->get('amp_enabled') && ($webVerdict !== "PASS" || $ampVerdict !== "PASS") && !in_array($indexingState, $successStates);
        $hasFailed = $webVerdict === "FAIL";

        if ($hasFailed || $ampEnabledFailed || $isUnlistedPassed || $ampDisabledPassed || !$webPassed) {
            $status->set('success', false);
        }

        $status->set('$inspectUrlIndexResponse', $inspectSeoResult);

        return $status;
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

    public function handleIndexingSuccess(): void {}

    public function handleIndexingFailure(Message $command, Message $article, InspectUrlIndexResponse $inspectSeoUrlIndexResponse): void {}
}
