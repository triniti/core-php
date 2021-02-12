<?php
declare(strict_types=1);

namespace Triniti\Curator;

use Gdbots\Ncr\Ncr;
use Gdbots\Pbj\Message;
use Gdbots\Pbj\MessageResolver;
use Gdbots\Pbj\Schema;
use Gdbots\Pbj\SchemaCurie;
use Gdbots\Pbj\WellKnown\NodeRef;
use Gdbots\Pbjx\EventSubscriber;
use Gdbots\Pbjx\Pbjx;
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

    public function __construct(Ncr $ncr)
    {
        $this->ncr = $ncr;
    }

    public static function getSubscribedEvents()
    {
        return [
            'gdbots:ncr:mixin:node-created'     => 'onNodeCreated',
            'gdbots:ncr:mixin:node-deleted'     => 'onNodeDeleted',
            'gdbots:ncr:mixin:node-expired'     => 'onNodeExpired',
            'gdbots:ncr:mixin:node-published'   => 'onNodePublished',
            'gdbots:ncr:mixin:node-scheduled'   => 'onNodeScheduled',
            'gdbots:ncr:mixin:node-unpublished' => 'onNodeUnpublished',
            'gdbots:ncr:mixin:node-updated'     => 'onNodeUpdated',
        ];
    }

    public function onNodeCreated(Message $event, Pbjx $pbjx): void
    {
        $this->syncTeasers($event, $pbjx);
    }

    public function onNodeDeleted(Message $event, Pbjx $pbjx): void
    {
        $this->deactivateTeasers($event, $pbjx);
    }

    public function onNodeExpired(Message $event, Pbjx $pbjx): void
    {
        $this->deactivateTeasers($event, $pbjx);
    }

    public function onNodePublished(Message $event, Pbjx $pbjx): void
    {
        $this->activateTeasers($event, $pbjx);
    }

    public function onNodeScheduled(Message $event, Pbjx $pbjx): void
    {
        $this->activateTeasers($event, $pbjx);
    }

    public function onNodeUnpublished(Message $event, Pbjx $pbjx): void
    {
        $this->deactivateTeasers($event, $pbjx);
    }

    public function onNodeUpdated(Message $event, Pbjx $pbjx): void
    {
        $this->syncTeasers($event, $pbjx);
    }

    protected function activateTeasers(Message $event, Pbjx $pbjx): void
    {
        if ($event->isReplay()) {
            return;
        }

        /** @var NodeRef $targetRef */
        $targetRef = $event->get('node_ref');
        if (!$this->isNodeRefSupported($targetRef)) {
            return;
        }

        if (!$this->shouldSyncTeasers($event)) {
            return;
        }

        static $class = null;
        if (null === $class) {
            $class = MessageResolver::resolveCurie(
                SchemaCurie::fromString("{$targetRef->getVendor()}:curator:command:publish-teaser")
            );
        }

        foreach ($this->getTeasers($targetRef, NodeStatus::DRAFT())['sync'] as $index => $teaser) {
            $teaserRef = NodeRef::fromNode($teaser);
            /** @var Message $command */
            $command = $class::create()
                ->set('node_ref', $teaserRef)
                ->set('publish_at', $event->get('published_at', $event->get('publish_at')));
            $pbjx->copyContext($event, $command);
            $command
                ->set('ctx_correlator_ref', $event->generateMessageRef())
                ->clear('ctx_app');
            $timestamp = strtotime(sprintf('+%d seconds', (5 + $index)));
            $pbjx->sendAt($command, $timestamp, "{$teaserRef}.publish");
        }
    }

    protected function deactivateTeasers(Message $event, Pbjx $pbjx): void
    {
        if ($event->isReplay()) {
            return;
        }

        /** @var NodeRef $targetRef */
        $targetRef = $event->get('node_ref');
        if (!$this->isNodeRefSupported($targetRef)) {
            return;
        }

        if (!$this->shouldSyncTeasers($event)) {
            return;
        }

        if ($event::schema()->hasMixin('gdbots:ncr:mixin:node-deleted')) {
            $operation = 'delete';
        } else if ($event::schema()->hasMixin('gdbots:ncr:mixin:node-expired')) {
            $operation = 'expire';
        } else {
            $operation = 'unpublish';
        }

        static $classes = null;
        if (null === $classes) {
            $vendor = $targetRef->getVendor();
            $classes = [
                'delete'    => MessageResolver::resolveCurie(SchemaCurie::fromString("{$vendor}:curator:command:delete-teaser")),
                'expire'    => MessageResolver::resolveCurie(SchemaCurie::fromString("{$vendor}:curator:command:expire-teaser")),
                'unpublish' => MessageResolver::resolveCurie(SchemaCurie::fromString("{$vendor}:curator:command:unpublish-teaser")),
            ];
        }

        foreach ($this->getTeasers($targetRef, NodeStatus::PUBLISHED())['all'] as $index => $teaser) {
            $teaserRef = NodeRef::fromNode($teaser);
            /** @var Message $command */
            $command = $classes[$operation]::create()->set('node_ref', $teaserRef);
            $pbjx->copyContext($event, $command);
            $command
                ->set('ctx_correlator_ref', $event->generateMessageRef())
                ->clear('ctx_app');
            $timestamp = strtotime(sprintf('+%d seconds', (5 + $index)));
            $pbjx->sendAt($command, $timestamp, "{$teaserRef}.{$operation}");
        }
    }

    protected function syncTeasers(Message $event, Pbjx $pbjx): void
    {
        if ($event->isReplay()) {
            return;
        }

        /** @var NodeRef $targetRef */
        $targetRef = $event->has('node')
            ? NodeRef::fromNode($event->get('node'))
            : $event->get('node_ref');

        if (!$this->isNodeRefSupported($targetRef)) {
            return;
        }

        if (!$this->shouldSyncTeasers($event)) {
            return;
        }

        $command = SyncTeaserV1::create()->set('target_ref', $targetRef);
        $pbjx->copyContext($event, $command);
        $command
            ->set('ctx_correlator_ref', $event->generateMessageRef())
            ->clear('ctx_app');

        $pbjx->sendAt($command, strtotime('+3 seconds'), "{$targetRef}.sync-teasers");
    }

    protected function isNodeRefSupported(NodeRef $targetRef): bool
    {
        static $validQNames = null;
        if (null === $validQNames) {
            $validQNames = [];
            foreach (MessageResolver::findAllUsingMixin('triniti:curator:mixin:teaserable:v1', false) as $curie) {
                $qname = SchemaCurie::fromString($curie)->getQName();
                $validQNames[$qname->getMessage()] = $qname;
            }
        }

        return isset($validQNames[$targetRef->getQName()->getMessage()]);
    }

    protected function shouldSyncTeasers(Message $event): bool
    {
        // override to implement your own check to block the sync
        // based on the event
        return true;
    }
}
