<?php
declare(strict_types=1);

namespace Triniti\Ovp;

use Gdbots\Ncr\Exception\InvalidArgumentException;
use Gdbots\Pbj\Message;
use Gdbots\Pbj\WellKnown\NodeRef;
use Gdbots\Schemas\Pbjx\Enum\Code;
use Triniti\Schemas\Ovp\Enum\TranscodingStatus;
use Triniti\Schemas\Ovp\Event\TranscodingCompletedV1;
use Triniti\Schemas\Ovp\Event\TranscodingFailedV1;
use Triniti\Schemas\Ovp\Event\TranscodingStartedV1;

trait TranscodeableAggregateTrait
{
    protected Message $node;
    protected NodeRef $nodeRef;

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

        switch ($command->fget('transcoding_status')) {
            case TranscodingStatus::COMPLETED:
                $event = TranscodingCompletedV1::create();
                break;

            case TranscodingStatus::PROCESSING:
                $event = TranscodingStartedV1::create();
                break;

            case TranscodingStatus::CANCELED:
            case TranscodingStatus::FAILED:
            case TranscodingStatus::UNKNOWN:
            default:
                $event = TranscodingFailedV1::create()
                    ->set('error_code', $command->get('error_code'))
                    ->set('error_message', $command->get('error_message'));
        }

        $event
            ->set('node_ref', $this->nodeRef)
            ->set('mediaconvert_job_arn', $command->get('mediaconvert_job_arn'))
            ->set('mediaconvert_queue_arn', $command->get('mediaconvert_queue_arn'));

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

    abstract protected function assertNodeRefMatches(NodeRef $nodeRef): void;

    abstract protected function copyContext(Message $command, Message $event): void;

    abstract protected function recordEvent(Message $event): void;
}
