<?php
declare(strict_types=1);

namespace Triniti\Boost;

use Gdbots\Ncr\AbstractSearchNodesRequestHandler;
use Gdbots\Pbj\Message;
use Gdbots\Pbj\MessageResolver;
use Gdbots\Pbjx\Pbjx;

class SearchSponsorsRequestHandler extends AbstractSearchNodesRequestHandler
{
    public static function handlesCuries(): array
    {
        // deprecated mixins, will be removed in 4.x
        $curies = MessageResolver::findAllUsingMixin('triniti:boost:mixin:search-sponsors-request:v1', false);
        $curies[] = 'triniti:boost:request:search-sponsors-request';
        return $curies;
    }

    protected function createSearchNodesResponse(Message $request, Pbjx $pbjx): Message
    {
        return MessageResolver::resolveCurie('*:boost:request:search-sponsors-response:v1')::create();
    }
}
