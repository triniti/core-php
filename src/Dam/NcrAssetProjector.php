<?php
declare(strict_types=1);

namespace Triniti\Dam;

use Gdbots\Ncr\NcrProjector;
use Gdbots\Pbj\Message;
use Gdbots\Pbjx\Pbjx;
use Triniti\Schemas\Ovp\Enum\TranscodingStatus;
use Triniti\Schemas\Ovp\Enum\TranscriptionStatus;

class NcrAssetProjector extends NcrProjector
{
    public static function getSubscribedEvents()
    {
        return [
            'triniti:ovp:event:transcoding-completed'     => 'onTranscodingCompleted',
            'triniti:ovp:event:transcoding-failed'        => 'onTranscodingFailed',
            'triniti:ovp:event:transcoding-started'       => 'onTranscodingStarted',
            'triniti:ovp:event:transcription-completed'   => 'onTranscriptionCompleted',
            'triniti:ovp:event:transcription-failed'      => 'onTranscriptionFailed',
            'triniti:ovp:event:transcription-started'     => 'onTranscriptionStarted',
        ];
    }

    public function onTranscodingCompleted(Message $event, Pbjx $pbjx): void
    {
        if (!$this->enabled) {
            return;
        }

        $nodeRef = $event->get('node_ref');
        if ('video-asset' !== $nodeRef->getLabel()) {
            return;
        }

        $node = $this->ncr->getNode($nodeRef, true);
        $node->set('transcoding_status', TranscodingStatus::COMPLETED());
        foreach (['mediaconvert_job_arn', 'mediaconvert_queue_arn'] as $field) {
            if ($event->has($field)) {
                $node->addToMap('tags', $field, $event->get($field));
            }
        }
        $this->projectNode($node, $event, $pbjx);
    }

    public function onTranscodingFailed(Message $event, Pbjx $pbjx): void
    {
        if (!$this->enabled) {
            return;
        }

        $nodeRef = $event->get('node_ref');
        if ('video-asset' !== $nodeRef->getLabel()) {
            return;
        }

        $node = $this->ncr->getNode($nodeRef, true);
        $node->set('transcoding_status', TranscodingStatus::FAILED());
        foreach (['mediaconvert_job_arn', 'mediaconvert_queue_arn'] as $field) {
            if ($event->has($field)) {
                $node->addToMap('tags', $field, $event->get($field));
            }
        }
        if ($event->has('error_code')) {
            $node->addToMap('tags', 'transcode_error_name', $event->get('error_code')->getName());
            $node->addToMap('tags', 'transcode_error_code', (string)$event->get('error_code')->getValue());
        }
        $this->projectNode($node, $event, $pbjx);
    }

    public function onTranscodingStarted(Message $event, Pbjx $pbjx): void
    {
        if (!$this->enabled) {
            return;
        }

        $nodeRef = $event->get('node_ref');
        if ('video-asset' !== $nodeRef->getLabel()) {
            return;
        }

        $node = $this->ncr->getNode($nodeRef, true);
        $node->set('transcoding_status', TranscodingStatus::PROCESSING());
        foreach (['mediaconvert_job_arn', 'mediaconvert_queue_arn'] as $field) {
            if ($event->has($field)) {
                $node->addToMap('tags', $field, $event->get($field));
            }
        }
        $this->projectNode($node, $event, $pbjx);
    }

    public function onTranscriptionCompleted(Message $event, Pbjx $pbjx): void
    {
        if (!$this->enabled) {
            return;
        }

        switch ($event->get('node_ref')->getLabel()) {
            case 'document-asset':
                $this->onDocumentTranscriptionCompleted($event, $pbjx);
                break;
            case 'video-asset':
                $this->onVideoTranscriptionCompleted($event, $pbjx);
                break;
            default:
                return;
        }
    }

    protected function onDocumentTranscriptionCompleted(Message $event, Pbjx $pbjx): void
    {
        $node = $this->ncr->getNode($event->get('node_ref'), true);
        foreach ($node->get('linked_refs', []) as $linkedRef) {
            if ('video-asset' !== $linkedRef->getLabel()) {
                continue;
            }
            $videoAsset = $this->ncr->getNode($linkedRef, true);
            $node->set('title', $videoAsset->get('title'));
            $this->projectNode($node, $event, $pbjx);
            break; // there _should_ only ever be one linked video asset
        }
    }

    protected function onVideoTranscriptionCompleted(Message $event, Pbjx $pbjx): void
    {
        $node = $this->ncr->getNode($event->get('node_ref'), true);
        $node->set('transcription_status', TranscriptionStatus::COMPLETED());
        foreach (['transcribe_job_name', 'transcribe_job_region', 'language_code'] as $field) {
            if ($event->has($field)) {
                $node->addToMap('tags', $field, $event->get($field));
            }
        }
        $this->projectNode($node, $event, $pbjx);
    }

    public function onTranscriptionFailed(Message $event, Pbjx $pbjx): void
    {
        if (!$this->enabled) {
            return;
        }

        $nodeRef = $event->get('node_ref');
        if ('video-asset' !== $nodeRef->getLabel()) {
            return;
        }

        $node = $this->ncr->getNode($nodeRef, true);
        $node->set('transcription_status', TranscriptionStatus::FAILED());
        foreach (['transcribe_job_name', 'transcribe_job_region', 'language_code'] as $field) {
            if ($event->has($field)) {
                $node->addToMap('tags', $field, $event->get($field));
            }
        }
        if ($event->has('error_code')) {
            $node->addToMap('tags', 'transcribe_error_name', $event->get('error_code')->getName());
            $node->addToMap('tags', 'transcribe_error_code', (string)$event->get('error_code')->getValue());
        }
        $this->projectNode($node, $event, $pbjx);
    }

    public function onTranscriptionStarted(Message $event, Pbjx $pbjx): void
    {
        if (!$this->enabled) {
            return;
        }

        $nodeRef = $event->get('node_ref');
        if ('video-asset' !== $nodeRef->getLabel()) {
            return;
        }

        $node = $this->ncr->getNode($nodeRef, true);
        $node->set('transcription_status', TranscriptionStatus::PROCESSING());
        foreach (['transcribe_job_name', 'transcribe_job_region', 'language_code'] as $field) {
            if ($event->has($field)) {
                $node->addToMap('tags', $field, $event->get($field));
            }
        }
        $this->projectNode($node, $event, $pbjx);
    }
}
