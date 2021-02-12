<?php
declare(strict_types=1);

namespace Triniti\Curator;

use Gdbots\Ncr\Ncr;
use Gdbots\Ncr\NcrProjector;
use Gdbots\Ncr\NcrSearch;
use Gdbots\Pbj\Message;
use Gdbots\Pbj\MessageResolver;
use Gdbots\Pbjx\Pbjx;
use Gdbots\Schemas\Ncr\Enum\NodeStatus;
use Psr\Cache\CacheItemPoolInterface;
use Triniti\Schemas\Curator\Command\RemoveTeaserSlottingV1;

class NcrTeaserProjector extends NcrProjector
{
    protected CacheItemPoolInterface $cache;

    public function __construct(Ncr $ncr, NcrSearch $ncrSearch, CacheItemPoolInterface $cache, bool $enabled = true)
    {
        parent::__construct($ncr, $ncrSearch, $enabled);
        $this->cache = $cache;
    }

    public static function getSubscribedEvents()
    {
        $vendor = MessageResolver::getDefaultVendor();
        return [
            "{$vendor}:curator:event:teaser-published"      => 'onTeaserPublished',
            "{$vendor}:curator:event:teaser-updated"        => 'onTeaserUpdated',
            'triniti:curator:event:teaser-slotting-removed' => 'onTeaserSlottingRemoved',
        ];
    }

    public function onTeaserPublished(Message $event, Pbjx $pbjx): void
    {
        $this->onNodeEvent($event, $pbjx);
        if ($event->isReplay()) {
            return;
        }

        $node = $this->ncr->getNode($event->get('node_ref'));

        if (!$node::schema()->hasMixin('triniti:curator:mixin:teaser')) {
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
        $command->clear('ctx_app');
        $pbjx->sendAt($command, strtotime('+5 seconds'));
    }

    public function onTeaserUpdated(Message $event, Pbjx $pbjx): void
    {
        $this->onNodeUpdated($event, $pbjx);
        if ($event->isReplay() || !$event->has('old_node')) {
            return;
        }

        /** @var Message $oldNode */
        $oldNode = $event->get('old_node');

        /** @var Message $newNode */
        $newNode = $event->get('new_node');

        if (
            !$oldNode::schema()->hasMixin('triniti:curator:mixin:teaser')
            || !$newNode::schema()->hasMixin('triniti:curator:mixin:teaser')
        ) {
            return;
        }

        if (!NodeStatus::PUBLISHED()->equals($newNode->get('status'))) {
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
        $command->clear('ctx_app');
        $pbjx->sendAt($command, strtotime('+5 seconds'));
    }

    public function onTeaserSlottingRemoved(Message $event, Pbjx $pbjx): void
    {
        $node = $this->ncr->getNode($event->get('node_ref'), true);

        $cacheKeys = [];
        foreach ($event->get('slotting_keys', []) as $key) {
            $node->removeFromMap('slotting', $key);
            $cacheKeys[] = "curator.slotting.{$key}.php";
        }

        $this->projectNode($node, $event, $pbjx);

        if ($event->isReplay()) {
            return;
        }

        $this->cache->deleteItems($cacheKeys);
    }
}
