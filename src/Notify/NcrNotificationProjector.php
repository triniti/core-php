<?php
declare(strict_types=1);

namespace Triniti\Notify;

use Gdbots\Ncr\NcrProjector;

class NcrNotificationProjector extends NcrProjector
{
    public static function getSubscribedEvents()
    {
        return [
            'triniti:notify:event:notification-failed' => 'onNodeEvent',
            'triniti:notify:event:notification-sent'   => 'onNodeEvent',

            // deprecated mixins, will be removed in 3.x
            'triniti:notify:mixin:notification-failed' => 'onNodeEvent',
            'triniti:notify:mixin:notification-sent'   => 'onNodeEvent',
        ];
    }
}
