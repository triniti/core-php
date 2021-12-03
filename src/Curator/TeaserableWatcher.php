<?php
declare(strict_types=1);

namespace Triniti\Curator;

use Gdbots\Ncr\Event\NodeProjectedEvent;
use Gdbots\Ncr\Ncr;
use Gdbots\Pbj\Message;
use Gdbots\Pbj\WellKnown\NodeRef;
use Gdbots\Pbjx\EventSubscriber;
use Gdbots\Schemas\Ncr\Command\DeleteNodeV1;
use Gdbots\Schemas\Ncr\Command\ExpireNodeV1;
use Gdbots\Schemas\Ncr\Command\PublishNodeV1;
use Gdbots\Schemas\Ncr\Command\UnpublishNodeV1;
use Gdbots\Schemas\Ncr\Enum\NodeStatus;
use Triniti\Schemas\Curator\Command\SyncTeaserV1;

/**
 * Responsible for watching changes to nodes that have
 * the mixin "triniti:curator:mixin:teaserable" and
 * keeping their associated teasers up to date.
 */
class TeaserableWatcher implements EventSubscriber
{
    use SyncTeaserTrait;

    public static function getSubscribedEvents(): array
    {
        return [
            'triniti:curator:mixin:teaserable.created'     => 'syncTeasers',
            'triniti:curator:mixin:teaserable.deleted'     => 'deactivateTeasers',
            'triniti:curator:mixin:teaserable.expired'     => 'deactivateTeasers',
            'triniti:curator:mixin:teaserable.published'   => 'activateTeasers',
            'triniti:curator:mixin:teaserable.scheduled'   => 'activateTeasers',
            'triniti:curator:mixin:teaserable.unpublished' => 'deactivateTeasers',
            'triniti:curator:mixin:teaserable.updated'     => 'syncTeasers',
        ];
    }

    public function __construct(Ncr $ncr)
    {
        $this->ncr = $ncr;
    }

    public function activateTeasers(NodeProjectedEvent $pbjxEvent): void
    {
        $event = $pbjxEvent->getLastEvent();
        $pbjx = $pbjxEvent::getPbjx();
        if ($event->isReplay()) {
            return;
        }

        if (!$this->shouldSyncTeasers($event)) {
            return;
        }

        $targetRef = $pbjxEvent->getNode()->generateNodeRef();
        foreach ($this->getTeasers($targetRef, NodeStatus::DRAFT())['sync'] as $index => $teaser) {
            $teaserRef = NodeRef::fromNode($teaser);
            $command = PublishNodeV1::create()
                ->set('node_ref', $teaserRef)
                ->set('publish_at', $event->get('published_at', $event->get('publish_at')));
            $pbjx->copyContext($event, $command);
            $timestamp = strtotime(sprintf('+%d seconds', (5 + $index)));
            $pbjx->sendAt($command, $timestamp, "{$teaserRef}.publish");
        }
    }

    public function deactivateTeasers(NodeProjectedEvent $pbjxEvent): void
    {
        $event = $pbjxEvent->getLastEvent();
        $pbjx = $pbjxEvent::getPbjx();
        if ($event->isReplay()) {
            return;
        }

        if (!$this->shouldSyncTeasers($event)) {
            return;
        }

        $eventType = $event::schema()->getCurie()->getMessage();
        if (str_ends_with($eventType, '-deleted')) {
            $operation = 'delete';
            $class = DeleteNodeV1::class;
        } else if (str_ends_with($eventType, '-expired')) {
            $operation = 'expire';
            $class = ExpireNodeV1::class;
        } else {
            $operation = 'unpublish';
            $class = UnpublishNodeV1::class;
        }

        $targetRef = $pbjxEvent->getNode()->generateNodeRef();
        foreach ($this->getTeasers($targetRef, NodeStatus::PUBLISHED())['all'] as $index => $teaser) {
            $teaserRef = NodeRef::fromNode($teaser);
            /** @var Message $class */
            $command = $class::create()->set('node_ref', $teaserRef);
            $pbjx->copyContext($event, $command);
            $timestamp = strtotime(sprintf('+%d seconds', (5 + $index)));
            $pbjx->sendAt($command, $timestamp, "{$teaserRef}.{$operation}");
        }
    }

    public function syncTeasers(NodeProjectedEvent $pbjxEvent): void
    {
        $event = $pbjxEvent->getLastEvent();
        $pbjx = $pbjxEvent::getPbjx();
        if ($event->isReplay()) {
            return;
        }

        if (!$this->shouldSyncTeasers($event)) {
            return;
        }

        $targetRef = $pbjxEvent->getNode()->generateNodeRef();
        $command = SyncTeaserV1::create()->set('target_ref', $targetRef);
        $pbjx->copyContext($event, $command);
        $pbjx->sendAt($command, strtotime('+3 seconds'), "{$targetRef}.sync-teasers");
    }

    protected function shouldSyncTeasers(Message $event): bool
    {
        // override to implement your own check to block the sync
        // based on the event
        return true;
    }
}
