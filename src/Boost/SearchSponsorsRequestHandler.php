<?php
declare(strict_types=1);

namespace Triniti\Boost;

use Gdbots\Ncr\AbstractSearchNodesRequestHandler;
use Gdbots\Pbj\Message;
use Gdbots\Pbj\MessageResolver;
use Gdbots\Pbjx\Pbjx;
use Triniti\Schemas\Boost\Request\SearchSponsorsResponseV1;

class SearchSponsorsRequestHandler extends AbstractSearchNodesRequestHandler
{
    public static function handlesCuries(): array
    {
        // deprecated mixins, will be removed in 3.x
        $curies = MessageResolver::findAllUsingMixin('triniti:boost:mixin:search-sponsors-request:v1', false);
        $curies[] = 'triniti:boost:request:search-sponsors-request';
        return $curies;
    }

    protected function createSearchNodesResponse(Message $request, Pbjx $pbjx): Message
    {
        return SearchSponsorsResponseV1::create();
    }
}
