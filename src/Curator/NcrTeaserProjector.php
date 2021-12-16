<?php
declare(strict_types=1);

namespace Triniti\Curator;

use Gdbots\Ncr\NcrProjector;

class NcrTeaserProjector extends NcrProjector
{
    public static function getSubscribedEvents(): array
    {
        return [
            'triniti:curator:mixin:teaser-slotting-removed' => 'onNodeEvent',
            'triniti:curator:event:teaser-slotting-removed' => 'onNodeEvent',
        ];
    }
}
