<?php
declare(strict_types=1);

namespace Triniti\News;

use Gdbots\Ncr\Event\NodeProjectedEvent;
use Gdbots\Pbj\Message;
use Gdbots\Pbjx\EventSubscriber;
use Gdbots\Pbjx\Pbjx;
use Gdbots\Schemas\Ncr\Enum\NodeStatus;
use Psr\Cache\CacheItemPoolInterface;
use Triniti\Schemas\News\Command\RemoveArticleSlottingV1;

class ArticleSlottingWatcher implements EventSubscriber
{
    protected CacheItemPoolInterface $cache;

    public static function getSubscribedEvents(): array
    {
        return [
            'triniti:news:mixin:article.published'        => 'onArticlePublished',
            'triniti:news:mixin:article.updated'          => 'onArticleUpdated',
            'triniti:news:mixin:article-slotting-removed' => 'onArticleSlottingRemoved',
            'triniti:news:event:article-slotting-removed' => 'onArticleSlottingRemoved',
        ];
    }

    public function __construct(CacheItemPoolInterface $cache)
    {
        $this->cache = $cache;
    }

    public function onArticlePublished(NodeProjectedEvent $pbjxEvent): void
    {
        $pbjx = $pbjxEvent::getPbjx();
        $event = $pbjxEvent->getLastEvent();
        $node = $pbjxEvent->getNode();

        if ($event->isReplay()) {
            return;
        }

        if (!$node->has('slotting')) {
            return;
        }

        $command = RemoveArticleSlottingV1::create()
            ->set('except_ref', $event->get('node_ref'));

        foreach ($node->get('slotting') as $key => $value) {
            $command->addToMap('slotting', $key, $value);
        }

        $pbjx->copyContext($event, $command);
        $pbjx->sendAt($command, strtotime('+5 seconds'));
    }

    public function onArticleUpdated(NodeProjectedEvent $pbjxEvent): void
    {
        $pbjx = $pbjxEvent::getPbjx();
        $event = $pbjxEvent->getLastEvent();

        if ($event->isReplay() || !$event->has('old_node')) {
            return;
        }

        /** @var Message $oldNode */
        $oldNode = $event->get('old_node');
        $newNode = $pbjxEvent->getNode();

        if (NodeStatus::PUBLISHED !== $newNode->fget('status')) {
            return;
        }

        $oldSlotting = $oldNode->get('slotting', []);
        $newSlotting = $newNode->get('slotting', []);
        ksort($oldSlotting);
        ksort($newSlotting);

        if ($oldSlotting === $newSlotting || empty($newSlotting)) {
            return;
        }

        $command = RemoveArticleSlottingV1::create()
            ->set('except_ref', $event->get('node_ref'));

        foreach ($newNode->get('slotting') as $key => $value) {
            $command->addToMap('slotting', $key, $value);
        }

        $pbjx->copyContext($event, $command);
        $pbjx->sendAt($command, strtotime('+5 seconds'));
    }

    public function onArticleSlottingRemoved(Message $event, Pbjx $pbjx): void
    {
        if ($event->isReplay()) {
            return;
        }

        $cacheKeys = [];
        foreach ($event->get('slotting_keys', []) as $key) {
            $cacheKeys[] = "news.slotting.{$key}.php";
        }

        $this->cache->deleteItems($cacheKeys);
    }
}
