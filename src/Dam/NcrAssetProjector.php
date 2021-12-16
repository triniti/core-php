<?php
declare(strict_types=1);

namespace Triniti\Dam;

use Gdbots\Ncr\NcrProjector;

class NcrAssetProjector extends NcrProjector
{
    public static function getSubscribedEvents(): array
    {
        return [
            'triniti:dam:event:asset-linked'            => 'onNodeEvent',
            'triniti:dam:event:asset-patched'           => 'onNodeEvent',
            'triniti:dam:event:asset-unlinked'          => 'onNodeEvent',
            'triniti:dam:event:gallery-asset-reordered' => 'onNodeEvent',

            // deprecated mixins, will be removed in 3.x
            'triniti:dam:mixin:asset-linked'            => 'onNodeEvent',
            'triniti:dam:mixin:asset-patched'           => 'onNodeEvent',
            'triniti:dam:mixin:asset-unlinked'          => 'onNodeEvent',
            'triniti:dam:mixin:gallery-asset-reordered' => 'onNodeEvent',
        ];
    }
}
