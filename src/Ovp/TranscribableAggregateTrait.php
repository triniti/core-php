<?php
declare(strict_types=1);

namespace Triniti\Ovp;

use Gdbots\Ncr\Exception\InvalidArgumentException;
use Gdbots\Pbj\Message;
use Gdbots\Pbj\WellKnown\NodeRef;
use Gdbots\Schemas\Pbjx\Enum\Code;
use Triniti\Schemas\Ovp\Enum\TranscriptionStatus;
use Triniti\Schemas\Ovp\Event\TranscriptionCompletedV1;
use Triniti\Schemas\Ovp\Event\TranscriptionFailedV1;
use Triniti\Schemas\Ovp\Event\TranscriptionStartedV1;

trait TranscribableAggregateTrait
{
    protected Message $node;
    protected NodeRef $nodeRef;

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

        switch ($command->fget('transcription_status')) {
            case TranscriptionStatus::COMPLETED:
                $event = TranscriptionCompletedV1::create();
                break;

            case TranscriptionStatus::PROCESSING:
                $event = TranscriptionStartedV1::create();
                break;

            case TranscriptionStatus::CANCELED:
            case TranscriptionStatus::FAILED:
            case TranscriptionStatus::UNKNOWN:
            default:
                $event = TranscriptionFailedV1::create()
                    ->set('error_code', $command->get('error_code'))
                    ->set('error_message', $command->get('error_message'));
        }

        $event
            ->set('node_ref', $this->nodeRef)
            ->set('transcribe_job_name', $command->get('transcribe_job_name'))
            ->set('transcribe_job_region', $command->get('transcribe_job_region'));

        $this->copyContext($command, $event);
        $this->recordEvent($event);
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

    abstract protected function assertNodeRefMatches(NodeRef $nodeRef): void;

    abstract protected function copyContext(Message $command, Message $event): void;

    abstract protected function recordEvent(Message $event): void;
}
