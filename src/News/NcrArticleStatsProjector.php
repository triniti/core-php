<?php
declare(strict_types=1);

namespace Triniti\News;

use Gdbots\Ncr\Exception\NodeNotFound;
use Gdbots\Ncr\Ncr;
use Gdbots\Ncr\NcrSearch;
use Gdbots\Pbj\Message;
use Gdbots\Pbj\MessageResolver;
use Gdbots\Pbj\WellKnown\Microtime;
use Gdbots\Pbj\WellKnown\NodeRef;
use Gdbots\Pbjx\DependencyInjection\PbjxProjector;
use Gdbots\Pbjx\EventSubscriber;
use Gdbots\Pbjx\EventSubscriberTrait;
use Gdbots\Pbjx\Pbjx;
use Gdbots\Schemas\Ncr\Enum\NodeStatus;
use Triniti\Schemas\News\Command\CollectArticleStatsV1;
use Triniti\Schemas\News\Mixin\ArticleStats\ArticleStatsV1Mixin;

class NcrArticleStatsProjector implements EventSubscriber, PbjxProjector
{
    use EventSubscriberTrait;

    protected Ncr $ncr;
    protected NcrSearch $ncrSearch;
    protected bool $indexOnReplay = false;

    public function __construct(Ncr $ncr, NcrSearch $ncrSearch, bool $indexOnReplay = false)
    {
        $this->ncr = $ncr;
        $this->ncrSearch = $ncrSearch;
        $this->indexOnReplay = $indexOnReplay;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        $vendor = MessageResolver::getDefaultVendor();
        return [
            "{$vendor}:news:event:*" => 'onEvent',
        ];
    }

    public function onArticleCreated(Message $event, Pbjx $pbjx): void
    {
        /** @var Message $article */
        $article = $event->get('node');
        $stats = $this->getOrCreateStats($article->generateNodeRef(), $event, $pbjx);
        $this->mergeArticle($article, $stats);
        $this->updateAndIndexStats($stats, $event, $pbjx);
    }

    public function onArticleDeleted(Message $event, Pbjx $pbjx): void
    {
        $statsRef = $this->createStatsRef($event->get('node_ref'));
        $this->ncr->deleteNode($statsRef, $this->createNcrContext($event));
        $this->ncrSearch->deleteNodes([$statsRef], $this->createNcrSearchContext($event));

        if ($event->isReplay()) {
            return;
        }

        $pbjx->cancelJobs(["{$statsRef}.collect"]);
    }

    public function onArticleExpired(Message $event, Pbjx $pbjx): void
    {
        $stats = $this->getOrCreateStats($event->get('node_ref'), $event, $pbjx);
        $stats->set('status', NodeStatus::EXPIRED());
        $this->updateAndIndexStats($stats, $event, $pbjx);
    }

    public function onArticleLocked(Message $event, Pbjx $pbjx): void
    {
        $stats = $this->getOrCreateStats($event->get('node_ref'), $event, $pbjx);
        $stats->set('status', NodeStatus::PENDING());
        $this->updateAndIndexStats($stats, $event, $pbjx);
    }

    public function onArticleMarkedAsDraft(Message $event, Pbjx $pbjx): void
    {
        $stats = $this->getOrCreateStats($event->get('node_ref'), $event, $pbjx);
        $stats->set('status', NodeStatus::DRAFT());
        $this->updateAndIndexStats($stats, $event, $pbjx);
    }

    public function onArticleMarkedAsPending(Message $event, Pbjx $pbjx): void
    {
        $stats = $this->getOrCreateStats($event->get('node_ref'), $event, $pbjx);
        $stats->set('status', NodeStatus::PENDING());
        $this->updateAndIndexStats($stats, $event, $pbjx);
    }

    public function onArticlePublished(Message $event, Pbjx $pbjx): void
    {
        $stats = $this->getOrCreateStats($event->get('node_ref'), $event, $pbjx);
        $stats
            ->set('status', NodeStatus::PUBLISHED())
            ->set('created_at', Microtime::fromDateTime($event->get('published_at')));
        $this->updateAndIndexStats($stats, $event, $pbjx);
    }

    public function onArticleScheduled(Message $event, Pbjx $pbjx): void
    {
        $stats = $this->getOrCreateStats($event->get('node_ref'), $event, $pbjx);
        $stats
            ->set('status', NodeStatus::SCHEDULED())
            ->set('created_at', Microtime::fromDateTime($event->get('publish_at')));
        $this->updateAndIndexStats($stats, $event, $pbjx);
    }

    public function onArticleStatsCollected(Message $event, Pbjx $pbjx): void
    {
        if (!$event->has('stats')) {
            return;
        }

        /** @var NodeRef $statsRef */
        $statsRef = $event->get('node_ref');
        $stats = $this->ncr->getNode($statsRef, true, $this->createNcrContext($event));

        foreach ($event->get('stats') as $name => $value) {
            if ($stats->has($name)) {
                $stats->set($name, $value);
            }
        }

        $this->updateAndIndexStats($stats, $event, $pbjx);
    }

    public function onArticleUnlocked(Message $event, Pbjx $pbjx): void
    {
        $stats = $this->getOrCreateStats($event->get('node_ref'), $event, $pbjx);
        $stats->set('status', NodeStatus::PENDING());
        $this->updateAndIndexStats($stats, $event, $pbjx);
    }

    public function onArticleUnpublished(Message $event, Pbjx $pbjx): void
    {
        $stats = $this->getOrCreateStats($event->get('node_ref'), $event, $pbjx);
        $stats->set('status', NodeStatus::DRAFT());
        $this->updateAndIndexStats($stats, $event, $pbjx);
    }

    public function onArticleUpdated(Message $event, Pbjx $pbjx): void
    {
        $stats = $this->getOrCreateStats($event->get('node_ref'), $event, $pbjx);
        $this->mergeArticle($event->get('new_node'), $stats);
        $this->updateAndIndexStats($stats, $event, $pbjx);
    }

    protected function updateAndIndexStats(Message $stats, Message $event, Pbjx $pbjx): void
    {
        $expectedEtag = $stats->get('etag');
        $stats
            ->set('updated_at', $event->get('occurred_at'))
            ->set('updater_ref', $event->get('ctx_user_ref'))
            ->set('last_event_ref', $event->generateMessageRef())
            ->set('etag', $stats->generateEtag([
                'etag',
                'updated_at',
                'updater_ref',
                'last_event_ref',
            ]));

        /** @var Message $stats */
        $this->ncr->putNode($stats, $expectedEtag, $this->createNcrContext($event));
        $this->indexStats($stats, $event, $pbjx);
        $this->cancelOrCreateCollectArticleStatsJob($stats, $event, $pbjx);
    }

    protected function indexStats(Message $stats, Message $event, Pbjx $pbjx): void
    {
        if (!$stats::schema()->hasMixin('gdbots:ncr:mixin:indexed')) {
            return;
        }

        if ($event->isReplay() && !$this->indexOnReplay) {
            return;
        }

        $this->ncrSearch->indexNodes([$stats], $this->createNcrSearchContext($event));
    }

    protected function createCollectArticleStats(Message $stats, Message $event, Pbjx $pbjx): Message
    {
        return CollectArticleStatsV1::create();
    }

    protected function cancelOrCreateCollectArticleStatsJob(Message $stats, Message $event, Pbjx $pbjx): void
    {
        if ($event->isReplay()) {
            return;
        }

        /** @var Message $stats */
        $statsRef = NodeRef::fromNode($stats);

        if (!NodeStatus::PUBLISHED()->equals($stats->get('status'))) {
            $pbjx->cancelJobs(["{$statsRef}.collect"]);
            return;
        }

        if (!$this->shouldCollectArticleStats($event, $stats)) {
            return;
        }

        /** @var Message $command */
        $command = $this->createCollectArticleStats($stats, $event, $pbjx)->set('node_ref', $statsRef);
        $pbjx->copyContext($event, $command);
        $command->clear('ctx_app');

        /** @var Microtime $createdAt */
        $createdAt = $stats->get('created_at');
        $publishedAt = $createdAt->toDateTime()->getTimestamp();

        if ($event::schema()->hasMixin('gdbots:ncr:mixin:node-updated')) {
            try {
                $pbjx->send($command);
            } catch (\Throwable $e) {
            }
        }

        if ($publishedAt >= strtotime('-1 hour')) {
            $sendAt = strtotime('+5 minutes');
        } elseif ($publishedAt >= strtotime('-3 hours')) {
            $sendAt = strtotime('+10 minutes');
        } elseif ($publishedAt >= strtotime('-6 hours')) {
            $sendAt = strtotime('+15 minutes');
        } elseif ($publishedAt >= strtotime('-12 hours')) {
            $sendAt = strtotime('+30 minutes');
        } elseif ($publishedAt >= strtotime('-1 day')) {
            $sendAt = strtotime('+1 hour');
        } elseif ($publishedAt >= strtotime('-2 days')) {
            $sendAt = strtotime('+3 hours');
        } elseif ($publishedAt >= strtotime('-4 days')) {
            $sendAt = strtotime('+6 hours');
        } elseif ($publishedAt >= strtotime('-1 week')) {
            $sendAt = strtotime('+12 hours');
        } elseif ($publishedAt >= strtotime('-2 weeks')) {
            $sendAt = strtotime('+1 day');
        } else {
            // after two weeks we don't need to continually collect stats
            // the world has moved on.
            return;
        }

        $pbjx->sendAt($command, $sendAt, "{$statsRef}.collect");
    }

    protected function shouldCollectArticleStats(Message $event, Message $stats): bool
    {
        // override to implement your own check
        return true;
    }

    protected function getOrCreateStats(NodeRef $articleRef, Message $event, Pbjx $pbjx): Message
    {
        try {
            $statsRef = $this->createStatsRef($articleRef);
            $stats = $this->ncr->getNode($statsRef, true, $this->createNcrContext($event));
        } catch (NodeNotFound $nf) {
            $stats = ArticleStatsV1Mixin::findOne()->createMessage(['_id' => $articleRef->getId()]);
        } catch (\Throwable $e) {
            throw $e;
        }

        return $stats;
    }

    protected function createStatsRef(NodeRef $articleRef): NodeRef
    {
        return NodeRef::fromString(str_replace('article:', 'article-stats:', $articleRef->toString()));
    }

    protected function mergeArticle(Message $article, Message $stats): void
    {
        $stats
            ->set('_id', $article->get('_id'))
            ->set('status', $article->get('status'))
            ->set('title', $article->get('title'));

        if ($article->has('published_at')) {
            $createdAt = Microtime::fromDateTime($article->get('published_at'));
        } else {
            $createdAt = $article->get('created_at');
        }

        $stats->set('created_at', $createdAt);
    }
}
