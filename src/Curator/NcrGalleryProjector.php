<?php
declare(strict_types=1);

namespace Triniti\Curator;

use Gdbots\Ncr\NcrProjector;
use Gdbots\Pbj\Message;
use Gdbots\Pbj\MessageResolver;
use Gdbots\Pbj\WellKnown\NodeRef;
use Gdbots\Pbjx\Pbjx;
use Triniti\Schemas\Curator\Command\UpdateGalleryImageCountV1;

class NcrGalleryProjector extends NcrProjector
{
    public static function getSubscribedEvents()
    {
        $vendor = MessageResolver::getDefaultVendor();
        return [
            "{$vendor}:curator:event:gallery-image-count-updated" => 'onGalleryImageCountUpdated', // deprecated
            "{$vendor}:dam:event:asset-created"                   => 'onAssetCreated',
            "{$vendor}:dam:event:asset-deleted"                   => 'onAssetDeletedOrExpired',
            "{$vendor}:dam:event:asset-expired"                   => 'onAssetDeletedOrExpired',
            'triniti:curator:event:gallery-image-count-updated'   => 'onGalleryImageCountUpdated',
            'triniti:dam:event:gallery-asset-reordered'           => 'onGalleryAssetReordered',
        ];
    }

    public function onAssetCreated(Message $event, Pbjx $pbjx): void
    {
        if ($event->isReplay()) {
            return;
        }

        /** @var Message $node */
        $node = $event->get('node');
        if (!$node::schema()->hasMixin('triniti:dam:mixin:image-asset') || !$node->has('gallery_ref')) {
            return;
        }

        $this->updateGalleryImageCount($event, $node->get('gallery_ref'), $pbjx);
    }

    public function onGalleryUpdated(Message $event, Pbjx $pbjx): void
    {
        $this->onNodeUpdated($event, $pbjx);
        $this->updateGalleryImageCount($event, $event->get('node_ref'), $pbjx);
    }

    public function onGalleryAssetReordered(Message $event, Pbjx $pbjx): void
    {
        if ($event->isReplay()) {
            return;
        }

        if ($event->has('gallery_ref')) {
            $this->updateGalleryImageCount($event, $event->get('gallery_ref'), $pbjx);
        }

        if ($event->has('old_gallery_ref')) {
            $this->updateGalleryImageCount($event, $event->get('old_gallery_ref'), $pbjx);
        }
    }

    public function onAssetDeletedOrExpired(Message $event, Pbjx $pbjx): void
    {
        if ($event->isReplay()) {
            return;
        }

        try {
            $node = $this->ncr->getNode($event->get('node_ref'), false);
        } catch (\Throwable $e) {
            return;
        }

        if (!$node::schema()->hasMixin('triniti:dam:mixin:image-asset') || !$node->has('gallery_ref')) {
            return;
        }

        $this->updateGalleryImageCount($event, $node->get('gallery_ref'), $pbjx);
    }

    public function onGalleryImageCountUpdated(Message $event, Pbjx $pbjx): void
    {
        $nodeRef = $event->get('node_ref');
        $node = $this->ncr->getNode($nodeRef);
        $node->set('image_count', $event->get('image_count'));
        $this->projectNode($node, $event, $pbjx);
    }

    public function onGalleryPublished(Message $event, Pbjx $pbjx): void
    {
        $this->onNodeEvent($event, $pbjx);
        $this->updateGalleryImageCount($event, $event->get('node_ref'), $pbjx);
    }

    protected function updateGalleryImageCount(Message $event, NodeRef $nodeRef, Pbjx $pbjx): void
    {
        if ($event->isReplay()) {
            return;
        }

        static $jobs = [];
        if (isset($jobs[$nodeRef->toString()])) {
            // it's possible to get a bunch of asset events in one batch but
            // we only need to count the gallery images one time per request
            return;
        }

        $jobs[$nodeRef->toString()] = true;
        $command = UpdateGalleryImageCountV1::create()->set('node_ref', $nodeRef);
        $pbjx->copyContext($event, $command);
        $command
            ->set('ctx_correlator_ref', $event->generateMessageRef())
            ->clear('ctx_app');

        $pbjx->sendAt($command, strtotime('+300 seconds'), "{$nodeRef}.update-gallery-image-count");
    }
}
