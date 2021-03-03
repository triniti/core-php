<?php
declare(strict_types=1);

namespace Triniti\Ovp;

use Gdbots\Ncr\NcrProjector;

class NcrVideoProjector extends NcrProjector
{
    public static function getSubscribedEvents()
    {
        return [
            'triniti:ovp:event:transcoding-completed'   => 'onNodeEvent',
            'triniti:ovp:event:transcoding-failed'      => 'onNodeEvent',
            'triniti:ovp:event:transcoding-started'     => 'onNodeEvent',
            'triniti:ovp:event:transcription-completed' => 'onNodeEvent',
            'triniti:ovp:event:transcription-failed'    => 'onNodeEvent',
            'triniti:ovp:event:transcription-started'   => 'onNodeEvent',
            'triniti:ovp.jwplayer:event:media-synced'   => 'onNodeEvent',
        ];
    }

    // may not be needed, still wip
    /*
    public function onTranscriptionCompleted(Message $event, Pbjx $pbjx): void
    {
        if ($this->enabled) {
            $this->onNodeEvent($event, $pbjx);
        }

        if ($event->isReplay()) {
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
    */
}
