<?php
declare(strict_types=1);

namespace Triniti\Dam;

use Gdbots\Ncr\Aggregate;
use Gdbots\Pbj\Message;
use Gdbots\Pbj\MessageResolver;
use Gdbots\Pbjx\Pbjx;
use Gdbots\Schemas\Ncr\Enum\NodeStatus;
use Triniti\Schemas\Dam\Event\AssetLinkedV1;
use Triniti\Schemas\Dam\Event\AssetPatchedV1;
use Triniti\Schemas\Dam\Event\AssetUnlinkedV1;
use Triniti\Schemas\Dam\Event\GalleryAssetReorderedV1;
use Triniti\Schemas\Ovp\Enum\TranscodingStatus;
use Triniti\Schemas\Ovp\Enum\TranscriptionStatus;
use Triniti\Schemas\Ovp\Event\TranscodingCompletedV1;
use Triniti\Schemas\Ovp\Event\TranscodingFailedV1;
use Triniti\Schemas\Ovp\Event\TranscodingStartedV1;
use Triniti\Schemas\Ovp\Event\TranscriptionCompletedV1;
use Triniti\Schemas\Ovp\Event\TranscriptionFailedV1;
use Triniti\Schemas\Ovp\Event\TranscriptionStartedV1;

class AssetAggregate extends Aggregate
{
    // todo: add tests for transcoding/transcription things
    // todo: add processing/canceled apply methods for transcoding/transcription

    protected function __construct(Message $node, Pbjx $pbjx, bool $syncAllEvents = false)
    {
        parent::__construct($node, $pbjx, $syncAllEvents);
        $this->node->set('status', NodeStatus::PUBLISHED());
    }

    protected function enrichNodeCreated(Message $event): void
    {
        parent::enrichNodeCreated($event);

        $node = $event->get('node');
        $node
            ->set('status', NodeStatus::PUBLISHED())
            // AssetEnricher SHOULD update file_etag
            ->clear('file_etag');
    }

    protected function enrichNodeUpdated(Message $event): void
    {
        parent::enrichNodeUpdated($event);

        $oldNode = $event->get('old_node');
        $newNode = $event->get('new_node');
        $newNode
            // file details SHOULD not change
            ->set('mime_type', $oldNode->get('mime_type'))
            ->set('file_size', $oldNode->get('file_size'))
            ->set('file_etag', $oldNode->get('file_etag'));

        // assets are only published, deleted, expired, enforce it.
        $status = $newNode->get('status');
        if (!NodeStatus::DELETED()->equals($status) && !NodeStatus::EXPIRED()->equals($status)) {
            $newNode->set('status', NodeStatus::PUBLISHED());
        }
    }

    public function linkAsset(Message $command): void
    {
        $event = AssetLinkedV1::create()
            ->set('node_ref', $this->nodeRef)
            ->set('linked_ref', $command->get('node_ref'));
        $this->pbjx->copyContext($command, $event);
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

    public function updateTranscodingStatus(Message $command): void
    {
        $nodeRef = $this->nodeRef;
        if ('video-asset' !== $nodeRef->getLabel()) {
            return;
        }
        $event = null;
        $transcodingStatus = $command->get('transcoding_status');
        switch ($transcodingStatus) {
            case TranscodingStatus::COMPLETED():
                $event = TranscodingCompletedV1::create();
                break;
            case TranscodingStatus::PROCESSING():
                $event = TranscodingStartedV1::create();
                break;
            case TranscodingStatus::CANCELED():
            case TranscodingStatus::FAILED():
            case TranscodingStatus::UNKNOWN():
            default:
                $event = TranscodingFailedV1::create();
                foreach (['error_code', 'error_message'] as $field) {
                    if ($command->has($field)) {
                        $event->set($field, $command->get($field));
                    }
                }
        }
        $event->set('node_ref', $this->nodeRef);

        foreach (['mediaconvert_job_arn', 'mediaconvert_queue_arn'] as $field) {
            if ($command->has($field)) {
                $event->set($field, $command->get($field));
            }
        }

        $this->pbjx->copyContext($command, $event);
        $this->recordEvent($event);
    }

    public function updateTranscriptionStatus(Message $command, ?string $videoAssetTitle = null): void
    {
        $label = $this->nodeRef->getLabel();
        if ($label !== 'video-asset' && $label !== 'document-asset') {
            return;
        }

        $event = null;
        $transcriptionStatus = $command->get('transcription_status');
        switch ($transcriptionStatus) {
            case TranscriptionStatus::COMPLETED():
                $event = TranscriptionCompletedV1::create();
                break;
            case TranscriptionStatus::PROCESSING():
                $event = TranscriptionStartedV1::create();
                break;
            case TranscriptionStatus::CANCELED():
            case TranscriptionStatus::FAILED():
            case TranscriptionStatus::UNKNOWN():
            default:
                $event = TranscriptionFailedV1::create();
                foreach (['error_code', 'error_message'] as $field) {
                    if ($command->has($field)) {
                        $event->set($field, $command->get($field));
                    }
                }
        }
        $event->set('node_ref', $this->nodeRef);

        foreach (['transcribe_job_name', 'transcribe_job_region', 'language_code'] as $field) {
            if ($command->has($field)) {
                $event->set($field, $command->get($field));
            }
        }

        if ($videoAssetTitle) {
            $event->addToMap('tags', 'video_asset_title', $videoAssetTitle);
        }

        $this->pbjx->copyContext($command, $event);
        $this->recordEvent($event);
    }

    protected function applyTranscodingFailed(Message $event): void
    {
        $this->node->set('transcoding_status', TranscodingStatus::FAILED());
        foreach (['mediaconvert_job_arn', 'mediaconvert_queue_arn'] as $field) {
            if ($event->has($field)) {
                $this->node->addToMap('tags', $field, $event->get($field));
            }
        }
        if ($event->has('error_code')) {
            $this->node->addToMap('tags', 'transcode_error_name', $event->get('error_code')->getName());
            $this->node->addToMap('tags', 'transcode_error_code', (string)$event->get('error_code')->getValue());
        }
    }

    protected function applyTranscodingStarted(Message $event): void
    {
        $this->node->set('transcoding_status', TranscodingStatus::PROCESSING());
        foreach (['mediaconvert_job_arn', 'mediaconvert_queue_arn'] as $field) {
            if ($event->has($field)) {
                $this->node->addToMap('tags', $field, $event->get($field));
            }
        }
        if ($event->has('error_code')) {
            $this->node->addToMap('tags', 'transcode_error_name', $event->get('error_code')->getName());
            $this->node->addToMap('tags', 'transcode_error_code', (string)$event->get('error_code')->getValue());
        }
    }

    protected function applyTranscodingCompleted(Message $event): void
    {
        $this->node->set('transcoding_status', TranscodingStatus::COMPLETED());
        foreach (['mediaconvert_job_arn', 'mediaconvert_queue_arn'] as $field) {
            if ($event->has($field)) {
                $this->node->addToMap('tags', $field, $event->get($field));
            }
        }
    }

    protected function applyTranscriptionCompleted(Message $event): void
    {
        switch ($event->get('node_ref')->getLabel()) {
            case 'document-asset':
                $this->applyDocumentTranscriptionCompleted($event);
                break;
            case 'video-asset':
                $this->applyVideoTranscriptionCompleted($event);
                break;
            default:
                return;
        }
    }

    protected function applyDocumentTranscriptionCompleted(Message $event): void
    {
        if ($event->isInMap('tags', 'video_asset_title')) {
            $this->node->set('title', $event->getFromMap('tags', 'video_asset_title'));
        }
    }

    protected function applyVideoTranscriptionCompleted(Message $event): void
    {
        $this->node->set('transcription_status', TranscriptionStatus::COMPLETED());
        foreach (['transcribe_job_name', 'transcribe_job_region', 'language_code'] as $field) {
            if ($event->has($field)) {
                $this->node->addToMap('tags', $field, $event->get($field));
            }
        }
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

    protected function createAssetPatched(Message $command): Message
    {
        $event = AssetPatchedV1::create();
        $this->pbjx->copyContext($command, $event);
        return $event;
    }

    protected function createGalleryAssetReordered(Message $command): Message
    {
        $event = GalleryAssetReorderedV1::create();
        $this->pbjx->copyContext($command, $event);
        return $event;
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
