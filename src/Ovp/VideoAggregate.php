<?php
declare(strict_types=1);

namespace Triniti\Ovp;

use Gdbots\Ncr\Aggregate;
use Gdbots\Pbj\Message;
use Gdbots\Pbj\MessageResolver;
use Gdbots\Pbj\WellKnown\NodeRef;
use Triniti\Dam\UrlService;
use Triniti\Schemas\Dam\AssetId;
use Triniti\Schemas\Ovp\Enum\TranscodingStatus;
use Triniti\Schemas\Ovp\Enum\TranscriptionStatus;
use Triniti\Schemas\Ovp\Event\TranscodingCompletedV1;
use Triniti\Schemas\Ovp\Event\TranscriptionCompletedV1;
use Triniti\Schemas\OvpJwplayer\Event\MediaSyncedV1;

class VideoAggregate extends Aggregate
{
    public function syncMedia(Message $command, array $fields = [], ?string $mediaId = null): void
    {
        $event = MediaSyncedV1::create()
            ->set('node_ref', $this->nodeRef)
            ->set('jwplayer_media_id', $mediaId);

        if (isset($fields['thumbnail_ref'])) {
            $event->set('thumbnail_ref', $fields['thumbnail_ref']);
        }

        if (isset($fields['jwplayer_caption_keys'])) {
            foreach ($fields['jwplayer_caption_keys'] as $language => $key) {
                $event->addToMap('jwplayer_caption_keys', $language, $key);
            }
        }

        $this->pbjx->copyContext($command, $event);
        $this->recordEvent($event);
    }

    public function updateTranscodingStatus(Message $command): void
    {
        if (!TranscodingStatus::COMPLETED()->equals($command->get('transcoding_status'))) {
            return; // this _should_ be enforced by the handler but might as well do it here too
        }

        $videoAssetRef = $command->get('node_ref');
        $event = TranscodingCompletedV1::create()
            ->set('node_ref', $this->nodeRef)
            ->addToMap('tags', 'video_asset_ref', $videoAssetRef->toString());

        if ($command->has('mediaconvert_job_arn')) {
            $event->set('mediaconvert_job_arn', $command->get('mediaconvert_job_arn'));
        }
        if ($command->has('mediaconvert_queue_arn')) {
            $event->set('mediaconvert_queue_arn', $command->get('mediaconvert_queue_arn'));
        }

        if (!$this->node->has('image_ref')) {
            $videoAssetId = AssetId::fromString($videoAssetRef->getId());
            $imageRef = sprintf(
                '%s:image-asset:image_jpg_%s_%s',
                $videoAssetRef->getVendor(),
                $videoAssetId->getDate(),
                $videoAssetId->getUuid()
            );
            $event->addToMap('tags', 'image_asset_ref', $imageRef);
        }

        $this->pbjx->copyContext($command, $event);
        $this->recordEvent($event);
    }

    public function updateTranscriptionStatus(Message $command): void
    {
        if (!TranscriptionStatus::COMPLETED()->equals($command->get('transcription_status'))) {
            return; // this _should_ be enforced by the handler but might as well do it here too
        }

        $videoAssetRef = $command->get('node_ref');
        $videoAssetId = AssetId::fromString($videoAssetRef->getId());
        $documentRef = NodeRef::fromString(sprintf(
            '%s:document-asset:document_vtt_%s_%s',
            $videoAssetRef->getVendor(),
            $videoAssetId->getDate(),
            $videoAssetId->getUuid()
        ));
        $event = TranscriptionCompletedV1::create()
            ->set('node_ref', $this->nodeRef)
            ->addToMap('tags', 'document_asset_ref', $documentRef->toString());
        $this->pbjx->copyContext($command, $event);
        $this->recordEvent($event);
    }

    protected function applyMediaSynced(Message $event): void
    {
        $this->node
            ->set('jwplayer_media_id', $event->get('jwplayer_media_id'))
            ->set('jwplayer_synced_at', $event->get('occurred_at')->getSeconds());
    }

    protected function applyTranscodingCompleted(Message $event): void
    {
        $assetRef = NodeRef::fromString($event->getFromMap('tags', 'video_asset_ref'));
        $assetId = AssetId::fromString($assetRef->getId());
        $this->node
            ->set('mezzanine_ref', $assetRef)
            ->set('mezzanine_url', ArtifactUrlService::getManifest($assetId))
            ->set('kaltura_mp4_url', ArtifactUrlService::getVideo($assetId));

        if ($event->isInMap('tags', 'image_asset_ref')) {
            $this->node->set('image_ref', NodeRef::fromString($event->getFromMap('tags', 'image_asset_ref')));
        }
    }

    protected function applyTranscodingFailed(Message $event): void
    {
        return; // override at site level if need be
    }

    protected function applyTranscodingStarted(Message $event): void
    {
        return; // override at site level if need be
    }

    protected function applyTranscriptionCompleted(Message $event): void
    {
        if (!$event->isInMap('tags', 'document_asset_ref')) {
            return;
        }

        $documentAssetRef = NodeRef::fromString($event->getFromMap('tags', 'document_asset_ref'));
        $assetId = AssetId::fromString($documentAssetRef->getId());
        $this->node
            ->set('caption_ref', $documentAssetRef)
            ->addToMap('caption_urls', 'en', UrlService::getUrl($assetId));
    }

    protected function applyTranscriptionFailed(Message $event): void
    {
        return; // override at site level if need be
    }

    protected function applyTranscriptionStarted(Message $event): void
    {
        return; // override at site level if need be
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
        $newName = str_replace('Video', 'Node', $name);
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
    protected function createNodeExpiredEvent(Message $command): Message
    {
        return MessageResolver::resolveCurie('*:ovp:event:video-expired:v1')::create();
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
        return MessageResolver::resolveCurie('*:ovp:event:video-locked:v1')::create();
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
        return MessageResolver::resolveCurie('*:ovp:event:video-marked-as-draft:v1')::create();
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
        return MessageResolver::resolveCurie('*:ovp:event:video-marked-as-pending:v1')::create();
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
        return MessageResolver::resolveCurie('*:ovp:event:video-published:v1')::create();
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
        return MessageResolver::resolveCurie('*:ovp:event:video-renamed:v1')::create();
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
        return MessageResolver::resolveCurie('*:ovp:event:video-scheduled:v1')::create();
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
        return MessageResolver::resolveCurie('*:ovp:event:video-unlocked:v1')::create();
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
        return MessageResolver::resolveCurie('*:ovp:event:video-unpublished:v1')::create();
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
        return MessageResolver::resolveCurie('*:ovp:event:video-updated:v1')::create();
    }
}