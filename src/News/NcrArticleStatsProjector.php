<?php
declare(strict_types=1);

namespace Triniti\News;

use Gdbots\Ncr\Aggregate;
use Gdbots\Ncr\Event\NodeProjectedEvent;
use Gdbots\Ncr\Exception\NodeNotFound;
use Gdbots\Ncr\Ncr;
use Gdbots\Ncr\NcrSearch;
use Gdbots\Pbj\Message;
use Gdbots\Pbj\MessageResolver;
use Gdbots\Pbj\WellKnown\Microtime;
use Gdbots\Pbj\WellKnown\NodeRef;
use Gdbots\Pbjx\DependencyInjection\PbjxProjector;
use Gdbots\Pbjx\EventSubscriber;
use Gdbots\Pbjx\Pbjx;
use Gdbots\Schemas\Ncr\Enum\NodeStatus;
use Triniti\Schemas\News\Command\CollectArticleStatsV1;

class NcrArticleStatsProjector implements EventSubscriber, PbjxProjector
{
    protected Ncr $ncr;
    protected NcrSearch $ncrSearch;
    protected bool $enabled;

    public static function getSubscribedEvents(): array
    {
        return [
            'triniti:news:mixin:article.projected'       => 'onArticleProjected',
            'triniti:news:event:article-stats-collected' => 'onArticleStatsCollected',

            // deprecated mixins, will be removed in 4.x
            'triniti:news:mixin:article-stats-collected' => 'onArticleStatsCollected',
        ];
    }

    public function __construct(Ncr $ncr, NcrSearch $ncrSearch, bool $enabled = true)
    {
        $this->ncr = $ncr;
        $this->ncrSearch = $ncrSearch;
        $this->enabled = $enabled;
    }

    public function onArticleProjected(NodeProjectedEvent $pbjxEvent): void
    {
        if (!$this->enabled) {
            return;
        }

        $pbjx = $pbjxEvent::getPbjx();
        $article = $pbjxEvent->getNode();
        $articleRef = $article->generateNodeRef();
        $lastEvent = $pbjxEvent->getLastEvent();

        if (NodeStatus::DELETED->value === $article->fget('status')) {
            $context = ['causator' => $lastEvent];
            $statsRef = $this->createStatsRef($articleRef);
            $this->ncr->deleteNode($statsRef, $context);
            $this->ncrSearch->deleteNodes([$statsRef], $context);

            if (!$lastEvent->isReplay()) {
                $pbjx->cancelJobs(["{$statsRef}.collect", "{$statsRef}.collect-now"]);
            }
            return;
        }

        $stats = $this->getOrCreateStats($articleRef, $lastEvent);
        $this->mergeArticle($article, $stats);
        $this->projectNode($stats, $lastEvent, $pbjxEvent::getPbjx());
    }

    public function onArticleStatsCollected(Message $event, Pbjx $pbjx): void
    {
        if (!$this->enabled) {
            return;
        }

        if (!$event->has('stats')) {
            return;
        }

        /** @var NodeRef $statsRef */
        $statsRef = $event->get('node_ref');

        $stats = $this->ncr->getNode($statsRef, true, ['causator' => $event]);

        foreach ($event->get('stats') as $name => $value) {
            if ($stats->has($name)) {
                $stats->set($name, $value);
            }
        }

        $this->projectNode($stats, $event, $pbjx);
    }

    protected function getOrCreateStats(NodeRef $articleRef, Message $event): Message
    {
        static $class = null;
        if (null === $class) {
            $class = MessageResolver::resolveCurie('*:news:node:article-stats:v1');
        }

        try {
            $statsRef = $this->createStatsRef($articleRef);
            $stats = $this->ncr->getNode($statsRef, true, ['causator' => $event]);
        } catch (NodeNotFound $nf) {
            $stats = $class::fromArray(['_id' => $articleRef->getId()]);
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

    protected function projectNode(Message $stats, Message $event, Pbjx $pbjx): void
    {
        $context = ['causator' => $event];
        $stats
            ->set('updated_at', $event->get('occurred_at'))
            ->set('updater_ref', $event->get('ctx_user_ref'))
            ->set('last_event_ref', $event->generateMessageRef())
            ->set('etag', Aggregate::generateEtag($stats));

        $this->ncr->putNode($stats, null, $context);
        $this->ncrSearch->indexNodes([$stats], $context);
        $pbjx->trigger($stats, 'projected', new NodeProjectedEvent($stats, $event), false, false);
        $this->cancelOrCreateCollectArticleStatsJob($stats, $event, $pbjx);
    }

    protected function cancelOrCreateCollectArticleStatsJob(Message $stats, Message $event, Pbjx $pbjx): void
    {
        if ($event->isReplay()) {
            return;
        }

        $statsRef = $stats->generateNodeRef();

        if (NodeStatus::PUBLISHED->value !== $stats->fget('status')) {
            $pbjx->cancelJobs(["{$statsRef}.collect", "{$statsRef}.collect-now"]);
            return;
        }

        if (!$this->shouldCollectArticleStats($event, $stats)) {
            return;
        }

        $command = CollectArticleStatsV1::create()->set('node_ref', $statsRef);
        $pbjx->copyContext($event, $command);

        /** @var Microtime $createdAt */
        $createdAt = $stats->get('created_at');
        $publishedAt = $createdAt->toDateTime()->getTimestamp();
        $schema = $event::schema();

        if ($schema->hasMixin('gdbots:ncr:mixin:node-updated')
            || $schema->usesCurie('gdbots:ncr:event:node-updated')
        ) {
            $pbjx->sendAt($command, strtotime('+5 seconds'), "{$statsRef}.collect-now");
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
}
