<?php
declare(strict_types=1);

namespace Triniti\Dam;

use Gdbots\Ncr\Aggregate;
use Gdbots\Pbj\Message;
use Gdbots\Pbj\MessageResolver;
use Gdbots\Pbjx\Pbjx;
use Gdbots\Schemas\Ncr\Enum\NodeStatus;
use Triniti\Schemas\Dam\Event\AssetUnlinkedV1;

class AssetAggregate extends Aggregate
{
    protected function __construct(Message $node, Pbjx $pbjx, bool $syncAllEvents = false)
    {
        parent::__construct($node, $pbjx, $syncAllEvents);
        // assets are only published, deleted, expired, enforce it.
        if (NodeStatus::DELETED !== $this->node->fget('status')
            && NodeStatus::EXPIRED !== $this->node->fget('status')
        ) {
            $this->node->set('status', NodeStatus::PUBLISHED());
        }
    }

    public function linkAsset(Message $command): void
    {
        $event = $this->createAssetLinked()
            ->set('node_ref', $this->nodeRef)
            ->set('linked_ref', $command->get('node_ref'));
        $this->pbjx->copyContext($command, $event);
        $this->recordEvent($event);
    }

    public function patchAsset(Message $command): void
    {
        $paths = $command->get('paths');
        $event = $this->createAssetPatched($command)
            ->set('node_ref', $this->nodeRef)
            ->addToSet('paths', $paths);

        foreach ($paths as $path) {
            // Add custom handling for each path to field here. For example, a field that uses
            // addToMap would need special handling that is possibly different than another field.
            // Another field might do something different with lists, etc.
            switch ($path) {
                case 'title':
                case 'display_title':
                case 'expires_at':
                case 'credit':
                case 'credit_url':
                case 'cta_text':
                case 'cta_url':
                case 'description':
                    $event->set($path, $command->get($path));
                    break;
            }
        }

        $this->recordEvent($event);
    }

    public function reorderGalleryAsset(Message $command): void
    {
        $event = $this->createGalleryAssetReordered($command)
            ->set('node_ref', $this->nodeRef)
            ->set('gallery_seq', $command->get('gallery_seqs')[$this->nodeRef->getId()])
            ->set('gallery_ref', $command->get('gallery_ref'))
            ->set('old_gallery_ref', $command->getFromMap('old_gallery_refs', $this->nodeRef->getId()));
        $this->recordEvent($event);
    }

    public function unlinkAsset(Message $command): void
    {
        $event = AssetUnlinkedV1::create()
            ->set('node_ref', $this->nodeRef)
            ->set('linked_ref', $command->get('node_ref'));
        $this->pbjx->copyContext($command, $event);
        $this->recordEvent($event);
    }

    protected function applyAssetLinked(Message $event): void
    {
        $this->node->addToSet('linked_refs', [$event->get('linked_ref')]);
    }

    protected function applyAssetUnlinked(Message $event): void
    {
        $this->node->removeFromSet('linked_refs', [$event->get('linked_ref')]);
    }

    protected function applyAssetPatched(Message $event): void
    {
        foreach ($event->get('paths') as $path) {
            $this->node->set($path, $event->get($path));
        }
    }

    protected function applyGalleryAssetReordered(Message $event): void
    {
        $this->node
            ->set('gallery_ref', $event->get('gallery_ref'))
            ->set('gallery_seq', $event->get('gallery_seq'));
    }

    protected function enrichNodeUpdated(Message $event): void
    {
        /** @var Message $oldNode */
        $oldNode = $event->get('old_node');

        /** @var Message $newNode */
        $newNode = $event->get('new_node');

        $newNode
            // file details SHOULD not change
            ->set('mime_type', $oldNode->get('mime_type'))
            ->set('file_size', $oldNode->get('file_size'))
            ->set('file_etag', $oldNode->get('file_etag'));

        // assets are only published, deleted, expired, enforce it.
        if (NodeStatus::DELETED !== $newNode->fget('status')
            && NodeStatus::EXPIRED !== $newNode->fget('status')
        ) {
            $newNode->set('status', NodeStatus::PUBLISHED());
        }

        parent::enrichNodeUpdated($event);
    }

    /**
     * This is for legacy uses of command/event mixins for common
     * ncr operations. It will be removed in 3.x.
     *
     * @param string $name
     * @param array  $arguments
     *
     * @return mixed
     */
    public function __call(string $name, array $arguments)
    {
        $newName = str_replace('Asset', 'Node', $name);
        if ($newName !== $name && is_callable([$this, $newName])) {
            return $this->$newName(...$arguments);
        }
    }

    /**
     * This is for legacy uses of command/event mixins for common
     * ncr operations. It will be removed in 3.x.
     *
     * @param Message $command
     *
     * @return Message
     *
     * @deprecated Will be removed in 3.x.
     */
    protected function createAssetLinked(Message $command): Message
    {
        return MessageResolver::resolveCurie('*:dam:event:asset-linked:v1')::create();
    }

    /**
     * This is for legacy uses of command/event mixins for common
     * ncr operations. It will be removed in 3.x.
     *
     * @param Message $command
     *
     * @return Message
     *
     * @deprecated Will be removed in 3.x.
     */
    protected function createAssetPatched(Message $command): Message
    {
        return MessageResolver::resolveCurie('*:dam:event:asset-patched:v1')::create();
    }

    /**
     * This is for legacy uses of command/event mixins for common
     * ncr operations. It will be removed in 3.x.
     *
     * @param Message $command
     *
     * @return Message
     *
     * @deprecated Will be removed in 3.x.
     */
    protected function createAssetUnlinked(Message $command): Message
    {
        return MessageResolver::resolveCurie('*:dam:event:asset-unlinked:v1')::create();
    }

    /**
     * This is for legacy uses of command/event mixins for common
     * ncr operations. It will be removed in 3.x.
     *
     * @param Message $command
     *
     * @return Message
     *
     * @deprecated Will be removed in 3.x.
     */
    protected function createGalleryAssetReordered(Message $command): Message
    {
        return MessageResolver::resolveCurie('*:dam:event:gallery-asset-reordered:v1')::create();
    }

    /**
     * This is for legacy uses of command/event mixins for common
     * ncr operations. It will be removed in 3.x.
     *
     * @param Message $command
     *
     * @return Message
     *
     * @deprecated Will be removed in 3.x.
     */
    protected function createNodeCreatedEvent(Message $command): Message
    {
        return MessageResolver::resolveCurie('*:dam:event:asset-created:v1')::create();
    }

    /**
     * This is for legacy uses of command/event mixins for common
     * ncr operations. It will be removed in 3.x.
     *
     * @param Message $command
     *
     * @return Message
     *
     * @deprecated Will be removed in 3.x.
     */
    protected function createNodeDeletedEvent(Message $command): Message
    {
        return MessageResolver::resolveCurie('*:dam:event:asset-deleted:v1')::create();
    }

    /**
     * This is for legacy uses of command/event mixins for common
     * ncr operations. It will be removed in 3.x.
     *
     * @param Message $command
     *
     * @return Message
     *
     * @deprecated Will be removed in 3.x.
     */
    protected function createNodeExpiredEvent(Message $command): Message
    {
        return MessageResolver::resolveCurie('*:dam:event:asset-expired:v1')::create();
    }

    /**
     * This is for legacy uses of command/event mixins for common
     * ncr operations. It will be removed in 3.x.
     *
     * @param Message $command
     *
     * @return Message
     *
     * @deprecated Will be removed in 3.x.
     */
    protected function createNodeLockedEvent(Message $command): Message
    {
        return MessageResolver::resolveCurie('*:dam:event:asset-locked:v1')::create();
    }

    /**
     * This is for legacy uses of command/event mixins for common
     * ncr operations. It will be removed in 3.x.
     *
     * @param Message $command
     *
     * @return Message
     *
     * @deprecated Will be removed in 3.x.
     */
    protected function createNodeMarkedAsDraftEvent(Message $command): Message
    {
        return MessageResolver::resolveCurie('*:dam:event:asset-marked-as-draft:v1')::create();
    }

    /**
     * This is for legacy uses of command/event mixins for common
     * ncr operations. It will be removed in 3.x.
     *
     * @param Message $command
     *
     * @return Message
     *
     * @deprecated Will be removed in 3.x.
     */
    protected function createNodeMarkedAsPendingEvent(Message $command): Message
    {
        return MessageResolver::resolveCurie('*:dam:event:asset-marked-as-pending:v1')::create();
    }

    /**
     * This is for legacy uses of command/event mixins for common
     * ncr operations. It will be removed in 3.x.
     *
     * @param Message $command
     *
     * @return Message
     *
     * @deprecated Will be removed in 3.x.
     */
    protected function createNodePublishedEvent(Message $command): Message
    {
        return MessageResolver::resolveCurie('*:dam:event:asset-published:v1')::create();
    }

    /**
     * This is for legacy uses of command/event mixins for common
     * ncr operations. It will be removed in 3.x.
     *
     * @param Message $command
     *
     * @return Message
     *
     * @deprecated Will be removed in 3.x.
     */
    protected function createNodeRenamedEvent(Message $command): Message
    {
        return MessageResolver::resolveCurie('*:dam:event:asset-renamed:v1')::create();
    }

    /**
     * This is for legacy uses of command/event mixins for common
     * ncr operations. It will be removed in 3.x.
     *
     * @param Message $command
     *
     * @return Message
     *
     * @deprecated Will be removed in 3.x.
     */
    protected function createNodeScheduledEvent(Message $command): Message
    {
        return MessageResolver::resolveCurie('*:dam:event:asset-scheduled:v1')::create();
    }

    /**
     * This is for legacy uses of command/event mixins for common
     * ncr operations. It will be removed in 3.x.
     *
     * @param Message $command
     *
     * @return Message
     *
     * @deprecated Will be removed in 3.x.
     */
    protected function createNodeUnlockedEvent(Message $command): Message
    {
        return MessageResolver::resolveCurie('*:dam:event:asset-unlocked:v1')::create();
    }

    /**
     * This is for legacy uses of command/event mixins for common
     * ncr operations. It will be removed in 3.x.
     *
     * @param Message $command
     *
     * @return Message
     *
     * @deprecated Will be removed in 3.x.
     */
    protected function createNodeUnpublishedEvent(Message $command): Message
    {
        return MessageResolver::resolveCurie('*:dam:event:asset-unpublished:v1')::create();
    }

    /**
     * This is for legacy uses of command/event mixins for common
     * ncr operations. It will be removed in 3.x.
     *
     * @param Message $command
     *
     * @return Message
     *
     * @deprecated Will be removed in 3.x.
     */
    protected function createNodeUpdatedEvent(Message $command): Message
    {
        return MessageResolver::resolveCurie('*:dam:event:asset-updated:v1')::create();
    }
}
