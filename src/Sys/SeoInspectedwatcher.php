<?php
declare(strict_types=1);

namespace Triniti\Sys;

use Gdbots\Ncr\Ncr;
use Gdbots\Pbjx\EventSubscriber;

class SeoInspectedWatcher implements EventSubscriber
{
    protected Ncr $ncr;

    public function __construct(Ncr $ncr)
    {
        $this->ncr = $ncr;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'triniti:sys:event:seo-inspected' => 'onSeoInspected',
        ];
    }
}
