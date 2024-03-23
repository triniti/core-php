<?php
declare(strict_types=1);

namespace Triniti\News;

use Gdbots\Ncr\Ncr;
use Gdbots\Pbj\Message;
use Gdbots\Pbj\MessageResolver;
use Gdbots\Pbjx\CommandHandler;
use Gdbots\Pbjx\Pbjx;


class ArticleInspectSeoHandler implements CommandHandler {
    private $retryCount;
    private $maxRetries;

    public static function handlesCuries(): array
    {
        $curies = MessageResolver::findAllUsingMixin('triniti:sys:command:inspect-seo:v1', false);
        $curies[] = 'triniti:sys:command:inspect-seo:v1';
        return $curies; 
    }
    
    public function __construct(Ncr $ncr, string $siteUrl, int $maxRetries)
    {
        $this->ncr = $ncr;
        $this->siteUrl = $siteUrl;
        $this->maxRetries = $maxRetries;
        $this->retryCount = 0;
    }

    public function handleCommand(Message $command, Pbjx $pbjx): void
    {
        $searchEngines = $command->get('search_engines', []);
        if (!empty($searchEngines) && !in_array('google', $searchEngines)) {
            return;
        }

        $nodeRef = $command->get('node_ref');
        $article = $this->ncr->getNode($nodeRef);
        $isIndexed = false;

        try {
            while(!$isIndexed && $this->retryCount < $this->maxRetries){
                $isIndexed = $this->checkIndexStatus($article);

                if ($isIndexed) {
                   $this->handleIndexingSuccess();
                } else {
                    $this->retryCount++;

                    if ($this->retryCount >= $this->maxRetries) {
                         $this->handleIndexingFailure();
                    }
                }
            }
        } catch (\Exception $e) {
            error_log($e->getMessage());
            $this->handleIndexingFailure();
        }
    }

    public function checkIndexStatus(Message $node): bool {
        $successStates = ['INDEXING_ALLOWED', 'SUCCESSFUL'];
        $failureStates = ['INDEXING_STATE_UNSPECIFIED'];

        $verdict = $response->get('inspectionResult')->get('indexStatusResult')->get('verdict');
        $indexingState = $response->get('inspectionResult')->get('indexStatusResult')->get('indexingState');
        
        if ($verdict === 'PASS' && in_array($indexingState, $successStates)) {
            return true;
        } 
        
        return false;
    }

    public function handleIndexingSuccess(): void {}

    public function handleIndexingFailure(): void {}
}
