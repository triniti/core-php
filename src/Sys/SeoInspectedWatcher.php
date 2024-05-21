<?php
declare(strict_types=1);

namespace Triniti\Sys;


use Gdbots\Ncr\Ncr;
use Gdbots\Pbj\Message;
use Gdbots\Pbjx\EventSubscriber;
use Gdbots\Pbjx\Pbjx;

class SeoInspectedWatcher implements EventSubscriber
{
    private Ncr $ncr;

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
    
    public function onSeoInspected(Message $event, Pbjx $pbjx): void {
    }
}
