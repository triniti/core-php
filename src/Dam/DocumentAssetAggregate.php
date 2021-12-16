<?php
declare(strict_types=1);

namespace Triniti\Dam;

use Gdbots\Pbj\Message;
use Triniti\Schemas\Ovp\Enum\TranscriptionStatus;
use Triniti\Schemas\Ovp\Event\TranscriptionCompletedV1;

class DocumentAssetAggregate extends AssetAggregate
{
    public function updateTranscriptionStatus(Message $command, Message $videoAsset): void
    {
        if (TranscriptionStatus::COMPLETED->value !== $command->fget('transcription_status')) {
            // we only care about completed for now.
            return;
        }

        $videoAssetRef = $videoAsset->generateNodeRef();
        $event = TranscriptionCompletedV1::create()
            ->set('node_ref', $this->nodeRef)
            ->set('transcribe_job_name', $command->get('transcribe_job_name'))
            ->set('transcribe_job_region', $command->get('transcribe_job_region'))
            ->set('language_code', $command->get('language_code'))
            ->addToMap('tags', 'video_asset_ref', $videoAssetRef->toString())
            ->addToMap('tags', 'video_asset_title', $videoAsset->get('title'));

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
