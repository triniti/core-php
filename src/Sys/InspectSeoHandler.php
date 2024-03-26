<?php
declare(strict_types=1);

namespace Triniti\Sys;

use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;
use Gdbots\Ncr\Ncr;
use Gdbots\Pbj\Message;
use Gdbots\Pbj\MessageResolver;
use Gdbots\Pbjx\CommandHandler;
use Gdbots\Pbjx\Pbjx;
use Gdbots\UriTemplate\UriTemplateService;
use Google\Service\SearchConsole\InspectUrlIndexResponse;


class InspectSeoHandler implements CommandHandler
{
//    private $retryCount;
//    // make max tries a flag
//    private $maxRetries;
    private String $siteUrl;
    private Ncr $ncr;
    private Key $key;

    public static function handlesCuries(): array
    {
        return [
            'triniti:sys:command:inspect-seo',
        ];
    }

    public function __construct(Ncr $ncr, string $siteUrl,
// int $maxRetries
Key $key
    )
    {
        $this->ncr = $ncr;
        $this->siteUrl = $siteUrl;
        $this->key = $key;
//        $this->maxRetries = $maxRetries;
//        $this->retryCount = 0;
    }

    public function handleCommand(Message $command, Pbjx $pbjx): void
    {
        $searchEngines = $command->get('search_engines', []);
        if (!empty($searchEngines) && !in_array('google', $searchEngines)) {
            return;
        }

        $nodeRef = $command->get('node_ref');
        $article = $this->ncr->getNode($nodeRef);


       $this->checkIndexStatus($article);
    }

    public function checkIndexStatus(Message $node): void {
        $successStates = ['INDEXING_ALLOWED', 'SUCCESSFUL'];

        $url = UriTemplateService::expand(
            "{$node::schema()->getQName()}.canonical", $node->getUriTemplateVars()
        );

        $urlStatus = null;

        try {
            $urlStatus = $this->getUrlIndexResponse($url);
        } catch (\Exception $e) {
           dump($e->getMessage());
           return;
        }

//        $webVerdict = $urlStatus->get('inspectionResult')->get('indexStatusResult')->get('verdict');
//        $indexingState = $urlStatus->get('inspectionResult')->get('indexStatusResult')->get('indexingState');
//        $ampVerdict = $urlStatus->get('inspectionResult')->get('ampResult')->get('verdict');

//        if ($node->get('is_unlisted') === 'true' || $node->get('amp_enabled') === "false"){
//            if ($webVerdict === 'PASS') {
//                error_log("Page should not be indexed");
//            }
//        }
//
//        if ($node->get('amp_enabled') === "true"){
//            if ($webVerdict === 'PASS' && $ampVerdict === 'PASS' && in_array($indexingState, $successStates)) {
//                $this->handleIndexingSuccess();
//            }
//        }
//
//        if ($webVerdict === 'PASS' && in_array($indexingState, $successStates)) {
//            $this->handleIndexingSuccess();
//        }
//
//        $this->handleIndexingFailure();
    }

    public function getUrlIndexResponse(String $url): InspectUrlIndexResponse {
        $request = new \Google_Service_SearchConsole_InspectUrlIndexRequest();
        $request->setSiteUrl('sc-domain:tmz.com');
        $request->setInspectionUrl($url);
        $client = new \Google_Client();
        $client->setAuthConfig(json_decode(base64_decode(Crypto::decrypt(getenv('GOOGLE_SEARCH_CONSOLE_API_SERVICE_ACCOUNT_OAUTH_CONFIG'), $this->key)), true));
        $client->addScope(\Google_Service_SearchConsole::WEBMASTERS_READONLY);
        $service = new \Google_Service_SearchConsole($client);

        return dump($service->urlInspection_index->inspect($request));
    }

    public function handleIndexingSuccess(): void {}

    public function handleIndexingFailure(): void {}
}
