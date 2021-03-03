<?php
declare(strict_types=1);

namespace Triniti\Dam;

use Gdbots\Ncr\Exception\InvalidArgumentException;
use Gdbots\Pbj\Message;
use Gdbots\Pbj\WellKnown\NodeRef;
use Gdbots\Schemas\Pbjx\Enum\Code;
use Triniti\Schemas\Ovp\Enum\TranscodingStatus;
use Triniti\Schemas\Ovp\Enum\TranscriptionStatus;
use Triniti\Schemas\Ovp\Event\TranscodingCompletedV1;
use Triniti\Schemas\Ovp\Event\TranscodingFailedV1;
use Triniti\Schemas\Ovp\Event\TranscodingStartedV1;
use Triniti\Schemas\Ovp\Event\TranscriptionCompletedV1;
use Triniti\Schemas\Ovp\Event\TranscriptionFailedV1;
use Triniti\Schemas\Ovp\Event\TranscriptionStartedV1;

class VideoAssetAggregate extends AssetAggregate
{
    public function updateTranscodingStatus(Message $command): void
    {
        if (!$this->node::schema()->hasMixin('triniti:ovp:mixin:transcodeable')) {
            throw new InvalidArgumentException(
                "Node [{$this->nodeRef}] must have [triniti:ovp:mixin:transcodeable]."
            );
        }

        /** @var NodeRef $nodeRef */
        $nodeRef = $command->get('node_ref');
        $this->assertNodeRefMatches($nodeRef);

        $event = match ($command->fget('transcoding_status')) {
            TranscodingStatus::COMPLETED => TranscodingCompletedV1::create(),
            TranscodingStatus::PROCESSING => TranscodingStartedV1::create(),
            default => TranscodingFailedV1::create()
                ->set('error_code', $command->get('error_code'))
                ->set('error_message', $command->get('error_message')),
        };

        $event
            ->set('node_ref', $this->nodeRef)
            ->set('mediaconvert_job_arn', $command->get('mediaconvert_job_arn'))
            ->set('mediaconvert_queue_arn', $command->get('mediaconvert_queue_arn'));

        $this->copyContext($command, $event);
        $this->recordEvent($event);
    }

    public function updateTranscriptionStatus(Message $command): void
    {
        if (!$this->node::schema()->hasMixin('triniti:ovp:mixin:transcribable')) {
            throw new InvalidArgumentException(
                "Node [{$this->nodeRef}] must have [triniti:ovp:mixin:transcribable]."
            );
        }

        /** @var NodeRef $nodeRef */
        $nodeRef = $command->get('node_ref');
        $this->assertNodeRefMatches($nodeRef);

        $event = match ($command->fget('transcription_status')) {
            TranscriptionStatus::COMPLETED => TranscriptionCompletedV1::create(),
            TranscriptionStatus::PROCESSING => TranscriptionStartedV1::create(),
            default => TranscriptionFailedV1::create()
                ->set('error_code', $command->get('error_code'))
                ->set('error_message', $command->get('error_message')),
        };

        $event
            ->set('node_ref', $this->nodeRef)
            ->set('transcribe_job_name', $command->get('transcribe_job_name'))
            ->set('transcribe_job_region', $command->get('transcribe_job_region'))
            ->set('language_code', $command->get('language_code'));

        $this->copyContext($command, $event);
        $this->recordEvent($event);
    }

    protected function applyTranscodingCompleted(Message $event): void
    {
        $this->node
            ->set('transcoding_status', TranscodingStatus::COMPLETED())
            ->removeFromMap('tags', 'transcode_error_code')
            ->removeFromMap('tags', 'transcode_error_name');

        foreach (['mediaconvert_job_arn', 'mediaconvert_queue_arn'] as $field) {
            $this->node->addToMap('tags', $field, $event->get($field));
        }
    }

    protected function applyTranscodingFailed(Message $event): void
    {
        $this->node
            ->set('transcoding_status', TranscodingStatus::FAILED())
            ->removeFromMap('tags', 'transcode_error_code')
            ->removeFromMap('tags', 'transcode_error_name');

        foreach (['mediaconvert_job_arn', 'mediaconvert_queue_arn'] as $field) {
            $this->node->addToMap('tags', $field, $event->get($field));
        }

        if ($event->has('error_code')) {
            $code = $event->get('error_code') ?: Code::UNKNOWN();
            $this->node->addToMap('tags', 'transcode_error_code', (string)$code->getValue());
            $this->node->addToMap('tags', 'transcode_error_name', $code->getName());
        }
    }

    protected function applyTranscodingStarted(Message $event): void
    {
        $this->node
            ->set('transcoding_status', TranscodingStatus::PROCESSING())
            ->removeFromMap('tags', 'transcode_error_code')
            ->removeFromMap('tags', 'transcode_error_name');

        foreach (['mediaconvert_job_arn', 'mediaconvert_queue_arn'] as $field) {
            $this->node->addToMap('tags', $field, $event->get($field));
        }
    }

    protected function applyTranscriptionCompleted(Message $event): void
    {
        $this->node
            ->set('transcription_status', TranscriptionStatus::COMPLETED())
            ->removeFromMap('tags', 'transcribe_error_code')
            ->removeFromMap('tags', 'transcribe_error_name');

        foreach (['transcribe_job_name', 'transcribe_job_region', 'language_code'] as $field) {
            $this->node->addToMap('tags', $field, $event->get($field));
        }
    }

    protected function applyTranscriptionFailed(Message $event): void
    {
        $this->node
            ->set('transcription_status', TranscriptionStatus::FAILED())
            ->removeFromMap('tags', 'transcribe_error_code')
            ->removeFromMap('tags', 'transcribe_error_name');

        foreach (['transcribe_job_name', 'transcribe_job_region', 'language_code'] as $field) {
            $this->node->addToMap('tags', $field, $event->get($field));
        }

        if ($event->has('error_code')) {
            $code = $event->get('error_code') ?: Code::UNKNOWN();
            $this->node->addToMap('tags', 'transcribe_error_code', (string)$code->getValue());
            $this->node->addToMap('tags', 'transcribe_error_name', $code->getName());
        }
    }

    protected function applyTranscriptionStarted(Message $event): void
    {
        $this->node
            ->set('transcription_status', TranscriptionStatus::PROCESSING())
            ->removeFromMap('tags', 'transcribe_error_code')
            ->removeFromMap('tags', 'transcribe_error_name');

        foreach (['transcribe_job_name', 'transcribe_job_region', 'language_code'] as $field) {
            $this->node->addToMap('tags', $field, $event->get($field));
        }
    }
}
