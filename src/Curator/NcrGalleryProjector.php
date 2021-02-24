<?php
declare(strict_types=1);

namespace Triniti\Curator;

use Gdbots\Ncr\Event\NodeProjectedEvent;
use Gdbots\Ncr\NcrProjector;
use Gdbots\Pbj\Message;
use Gdbots\Pbj\WellKnown\NodeRef;
use Gdbots\Pbjx\Pbjx;
use Triniti\Schemas\Curator\Command\UpdateGalleryImageCountV1;

class NcrGalleryProjector extends NcrProjector
{
    public static function getSubscribedEvents()
    {
        return [
            'triniti:curator:event:gallery-image-count-updated' => 'onNodeEvent',
            'triniti:curator:mixin:gallery.published'           => 'onGalleryProjected',
            'triniti:curator:mixin:gallery.updated'             => 'onGalleryProjected',
            'triniti:dam:event:gallery-asset-reordered'         => 'onGalleryAssetReordered',
            'triniti:dam:mixin:image-asset.created'             => 'onImageAssetProjected',
            'triniti:dam:mixin:image-asset.deleted'             => 'onImageAssetProjected',
            'triniti:dam:mixin:image-asset.expired'             => 'onImageAssetProjected',

            // deprecated mixins, will be removed in 3.x
            'triniti:curator:mixin:gallery-image-count-updated' => 'onNodeEvent',
            'triniti:dam:mixin:gallery-asset-reordered'         => 'onGalleryAssetReordered',
        ];
    }

    public function onGalleryAssetReordered(Message $event, Pbjx $pbjx): void
    {
        if ($event->has('gallery_ref')) {
            $this->updateGalleryImageCount($event, $event->get('gallery_ref'), $pbjx);
        }

        if ($event->has('old_gallery_ref')) {
            $this->updateGalleryImageCount($event, $event->get('old_gallery_ref'), $pbjx);
        }
    }

    public function onGalleryProjected(NodeProjectedEvent $pbjxEvent): void
    {
        $event = $pbjxEvent->getLastEvent();
        $this->updateGalleryImageCount($event, $event->get('node_ref'), $pbjxEvent::getPbjx());
    }

    public function onImageAssetProjected(NodeProjectedEvent $pbjxEvent): void
    {
        $event = $pbjxEvent->getLastEvent();
        $node = $pbjxEvent->getNode();
        if (!$node->has('gallery_ref')) {
            return;
        }

        $this->updateGalleryImageCount($event, $node->get('gallery_ref'), $pbjxEvent::getPbjx());
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
