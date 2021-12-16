<?php
declare(strict_types=1);

namespace Triniti\Ovp;

use Gdbots\Ncr\NcrProjector;

class NcrVideoProjector extends NcrProjector
{
    public static function getSubscribedEvents(): array
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
}
