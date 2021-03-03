<?php
declare(strict_types=1);

namespace Triniti\Dam;

use Gdbots\Pbj\Message;
use Triniti\Schemas\Ovp\Enum\TranscriptionStatus;
use Triniti\Schemas\Ovp\Event\TranscriptionCompletedV1;
use Triniti\Schemas\Ovp\Event\TranscriptionFailedV1;
use Triniti\Schemas\Ovp\Event\TranscriptionStartedV1;

class DocumentAssetAggregate extends AssetAggregate
{
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

        $this->copyContext($command, $event);
        $this->recordEvent($event);
    }

    protected function applyTranscriptionCompleted(Message $event): void
    {
        if ($event->isInMap('tags', 'video_asset_title')) {
            $this->node->set('title', $event->getFromMap('tags', 'video_asset_title'));
        }
    }
}
