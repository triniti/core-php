<?php
declare(strict_types=1);

namespace Triniti\Sys;

use Gdbots\Pbjx\EventSubscriber;

class SeoInspectedWatcher implements EventSubscriber
{
    public static function getSubscribedEvents(): array
    {
        return [
            'triniti:sys:event:seo-inspected' => 'onSeoInspected',
        ];
    }
}
