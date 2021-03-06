<?php
declare(strict_types=1);

namespace Triniti\Curator;

use Gdbots\Ncr\Event\NodeProjectedEvent;
use Gdbots\Pbj\Message;
use Gdbots\Pbjx\EventSubscriber;
use Gdbots\Pbjx\Pbjx;
use Gdbots\Schemas\Ncr\Enum\NodeStatus;
use Psr\Cache\CacheItemPoolInterface;
use Triniti\Schemas\Curator\Command\RemoveTeaserSlottingV1;

class TeaserSlottingWatcher implements EventSubscriber
{
    protected CacheItemPoolInterface $cache;

    public static function getSubscribedEvents()
    {
        return [
            'triniti:curator:mixin:teaser.published'        => 'onTeaserPublished',
            'triniti:curator:mixin:teaser.updated'          => 'onTeaserUpdated',
            'triniti:curator:mixin:teaser-slotting-removed' => 'onTeaserSlottingRemoved',
            'triniti:curator:event:teaser-slotting-removed' => 'onTeaserSlottingRemoved',
        ];
    }

    public function __construct(CacheItemPoolInterface $cache)
    {
        $this->cache = $cache;
    }

    public function onTeaserPublished(NodeProjectedEvent $pbjxEvent): void
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

        $command = RemoveTeaserSlottingV1::create()
            ->set('except_ref', $event->get('node_ref'));

        foreach ($node->get('slotting') as $key => $value) {
            $command->addToMap('slotting', $key, $value);
        }

        $pbjx->copyContext($event, $command);
        $pbjx->sendAt($command, strtotime('+5 seconds'));
    }

    public function onTeaserUpdated(NodeProjectedEvent $pbjxEvent): void
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

        $command = RemoveTeaserSlottingV1::create()
            ->set('except_ref', $event->get('node_ref'));

        foreach ($newNode->get('slotting') as $key => $value) {
            $command->addToMap('slotting', $key, $value);
        }

        $pbjx->copyContext($event, $command);
        $pbjx->sendAt($command, strtotime('+5 seconds'));
    }

    public function onTeaserSlottingRemoved(Message $event, Pbjx $pbjx): void
    {
        if ($event->isReplay()) {
            return;
        }

        $cacheKeys = [];
        foreach ($event->get('slotting_keys', []) as $key) {
            $cacheKeys[] = "curator.slotting.{$key}.php";
        }

        $this->cache->deleteItems($cacheKeys);
    }
}
