<?php
declare(strict_types=1);

namespace Triniti\Apollo;

use Gdbots\Ncr\Ncr;
use Gdbots\Pbjx\DependencyInjection\PbjxValidator;
use Gdbots\Pbjx\EventSubscriber;
use Gdbots\Schemas\Ncr\Enum\NodeStatus;

class ReactionsValidator implements EventSubscriber, PbjxValidator
{
    protected Ncr $ncr;

    public static function getSubscribedEvents(): array
    {
        return [];
    }

    public function __construct(Ncr $ncr)
    {
        $this->ncr = $ncr;
    }

    public static function getValidReactions(): array
    {
        // override to implement your own reaction types
        return [
            'love',
            'haha',
            'wow',
            'wtf',
            'trash',
            'sad',
        ];
    }
}

