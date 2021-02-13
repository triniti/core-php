<?php
declare(strict_types=1);

namespace Triniti\OvpJwplayer;

use Gdbots\Ncr\Ncr;
use Gdbots\Pbj\Message;
use Gdbots\Pbj\MessageResolver;
use Gdbots\Pbj\WellKnown\NodeRef;
use Gdbots\Pbjx\EventSubscriber;
use Gdbots\Pbjx\Pbjx;
use Triniti\Schemas\OvpJwplayer\Command\SyncMediaV1;

class JwplayerWatcher implements EventSubscriber
{
    protected Ncr $ncr;

    public function __construct(Ncr $ncr)
    {
        $this->ncr = $ncr;
    }

    public static function getSubscribedEvents()
    {
        $vendor = MessageResolver::getDefaultVendor();
        return [
            "{$vendor}:ovp:event:video-created"           => 'onVideoCreated',
            "{$vendor}:ovp:event:video-deleted"           => 'onVideoStatusChanged',
            "{$vendor}:ovp:event:video-expired"           => 'onVideoStatusChanged',
            "{$vendor}:ovp:event:video-marked-as-draft"   => 'onVideoStatusChanged',
            "{$vendor}:ovp:event:video-marked-as-pending" => 'onVideoStatusChanged',
            "{$vendor}:ovp:event:video-renamed"           => 'onVideoStatusChanged',
            "{$vendor}:ovp:event:video-scheduled"         => 'onVideoStatusChanged',
            "{$vendor}:ovp:event:video-published"         => 'onVideoStatusChanged',
            "{$vendor}:ovp:event:video-updated"           => 'onVideoUpdated',
            "{$vendor}:ovp:event:video-unpublished"       => 'onVideoStatusChanged',
            "triniti:ovp:event:transcoding-completed"     => 'onTranscodingCompleted',
            "triniti:ovp:event:transcription-completed"   => 'onTranscriptionCompleted',
        ];
    }

    public function onVideoCreated(Message $event, Pbjx $pbjx): void
    {
        if ($event->isReplay()) {
            return;
        }

        $this->syncMedia(
            $event,
            $pbjx,
            $event->get('node')->generateNodeRef(),
            ['captions', 'thumbnail']
        );
    }

    public function onVideoStatusChanged(Message $event, Pbjx $pbjx): void
    {
        if ($event->isReplay()) {
            return;
        }

        $this->syncMedia($event, $pbjx, $event->get('node_ref'));
    }

    public function onVideoUpdated(Message $event, Pbjx $pbjx): void
    {
        if ($event->isReplay()) {
            return;
        }

        $nodeRef = $event->get('node_ref');
        $node = $event->get('old_node') ?: $this->ncr->getNode($nodeRef);
        $newNode = $event->get('new_node');
        $fields = [];

        $newImageRef = $newNode->get('poster_image_ref', $newNode->get('image_ref'));
        $imageRef = $node->get('poster_image_ref', $node->get('image_ref'));
        if ($newImageRef && (!$imageRef || !$newImageRef->equals($imageRef))) {
            $fields[] = 'thumbnail';
        }

        if ($newNode->has('caption_urls')) {
            foreach ($newNode->get('caption_urls') as $language => $url) {
                if ($url !== $node->getFromMap('caption_urls', $language)) {
                    $fields[] = 'captions';
                    break;
                }
            }
        }

        $this->syncMedia($event, $pbjx, $nodeRef, $fields);
    }

    public function onTranscodingCompleted(Message $event, Pbjx $pbjx): void
    {
        if ($event->isReplay()) {
            return;
        }

        $nodeRef = $event->get('node_ref');
        if ('video' !== $nodeRef->getLabel()) {
            return;
        }

        $fields = [];
        if ($event->isInMap('tags', 'image_asset_ref')) {
            $fields[] = 'thumbnail';
        }

        $this->syncMedia($event, $pbjx, $nodeRef, $fields);
    }

    public function onTranscriptionCompleted(Message $event, Pbjx $pbjx): void
    {
        if ($event->isReplay()) {
            return;
        }

        $nodeRef = $event->get('node_ref');
        if ('video' !== $nodeRef->getLabel()) {
            return;
        }

        $this->syncMedia($event, $pbjx, $nodeRef, ['captions']);
    }

    protected function syncMedia(Message $event, Pbjx $pbjx, NodeRef $nodeRef, array $fields = []): void
    {
        $command = SyncMediaV1::create()
            ->set('node_ref', $nodeRef)
            ->addToSet('fields', $fields);
        $pbjx->copyContext($event, $command);
        $command
            ->set('ctx_correlator_ref', $event->generateMessageRef())
            ->clear('ctx_app');
        $pbjx->sendAt($command, strtotime('+5 seconds'), "{$nodeRef}.sync-jwplayer-media");
    }
}
