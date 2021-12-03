<?php
declare(strict_types=1);

namespace Triniti\OvpJwplayer;

use Gdbots\Ncr\Event\NodeProjectedEvent;
use Gdbots\Pbj\Message;
use Gdbots\Pbj\WellKnown\NodeRef;
use Gdbots\Pbjx\EventSubscriber;
use Gdbots\Pbjx\Pbjx;
use Triniti\Schemas\OvpJwplayer\Command\SyncMediaV1;

class JwplayerWatcher implements EventSubscriber
{
    public static function getSubscribedEvents(): array
    {
        return [
            'triniti:ovp.jwplayer:mixin:has-media.created'                 => 'onVideoCreated',
            'triniti:ovp.jwplayer:mixin:has-media.deleted'                 => 'onVideoEvent',
            'triniti:ovp.jwplayer:mixin:has-media.expired'                 => 'onVideoEvent',
            'triniti:ovp.jwplayer:mixin:has-media.marked-as-draft'         => 'onVideoEvent',
            'triniti:ovp.jwplayer:mixin:has-media.marked-as-pending'       => 'onVideoEvent',
            'triniti:ovp.jwplayer:mixin:has-media.renamed'                 => 'onVideoEvent',
            'triniti:ovp.jwplayer:mixin:has-media.scheduled'               => 'onVideoEvent',
            'triniti:ovp.jwplayer:mixin:has-media.published'               => 'onVideoEvent',
            'triniti:ovp.jwplayer:mixin:has-media.updated'                 => 'onVideoUpdated',
            'triniti:ovp.jwplayer:mixin:has-media.transcoding-completed'   => 'onTranscodingCompleted',
            'triniti:ovp.jwplayer:mixin:has-media.transcription-completed' => 'onTranscriptionCompleted',
        ];
    }

    public function onVideoCreated(NodeProjectedEvent $pbjxEvent): void
    {
        $pbjx = $pbjxEvent::getPbjx();
        $event = $pbjxEvent->getLastEvent();
        $node = $pbjxEvent->getNode();

        if ($event->isReplay()) {
            return;
        }

        $this->syncMedia($event, $pbjx, $node->generateNodeRef(), ['captions', 'thumbnail']);
    }

    public function onVideoEvent(NodeProjectedEvent $pbjxEvent): void
    {
        $pbjx = $pbjxEvent::getPbjx();
        $event = $pbjxEvent->getLastEvent();

        if ($event->isReplay()) {
            return;
        }

        $this->syncMedia($event, $pbjx, $event->get('node_ref'));
    }

    public function onVideoUpdated(NodeProjectedEvent $pbjxEvent): void
    {
        $pbjx = $pbjxEvent::getPbjx();
        $event = $pbjxEvent->getLastEvent();
        $newNode = $pbjxEvent->getNode();

        if ($event->isReplay()) {
            return;
        }

        if (!$event->has('old_node')) {
            $this->syncMedia($event, $pbjx, $newNode->generateNodeRef());
            return;
        }

        /** @var Message $oldNode */
        $oldNode = $event->get('old_node');
        $fields = [];

        $oldImageRef = $oldNode->fget('poster_image_ref', $oldNode->fget('image_ref'));
        $newImageRef = $newNode->fget('poster_image_ref', $newNode->fget('image_ref'));

        if ($newImageRef && $newImageRef !== $oldImageRef) {
            $fields[] = 'thumbnail';
        }

        if ($newNode->has('caption_urls')) {
            foreach ($newNode->get('caption_urls') as $language => $url) {
                if ($url !== $oldNode->getFromMap('caption_urls', $language)) {
                    $fields[] = 'captions';
                    break;
                }
            }
        }

        $this->syncMedia($event, $pbjx, $newNode->generateNodeRef(), $fields);
    }

    public function onTranscodingCompleted(NodeProjectedEvent $pbjxEvent): void
    {
        $pbjx = $pbjxEvent::getPbjx();
        $event = $pbjxEvent->getLastEvent();
        $node = $pbjxEvent->getNode();

        if ($event->isReplay()) {
            return;
        }

        $fields = [];
        if ($event->isInMap('tags', 'image_asset_ref')) {
            $fields[] = 'thumbnail';
        }

        $this->syncMedia($event, $pbjx, $node->generateNodeRef(), $fields);
    }

    public function onTranscriptionCompleted(NodeProjectedEvent $pbjxEvent): void
    {
        $pbjx = $pbjxEvent::getPbjx();
        $event = $pbjxEvent->getLastEvent();
        $node = $pbjxEvent->getNode();

        if ($event->isReplay()) {
            return;
        }

        $this->syncMedia($event, $pbjx, $node->generateNodeRef(), ['captions']);
    }

    protected function syncMedia(Message $event, Pbjx $pbjx, NodeRef $nodeRef, array $fields = []): void
    {
        $command = SyncMediaV1::create()
            ->set('node_ref', $nodeRef)
            ->addToSet('fields', $fields);
        $pbjx->copyContext($event, $command);
        $pbjx->sendAt($command, strtotime('+5 seconds'), "{$nodeRef}.sync-jwplayer-media");
    }
}
