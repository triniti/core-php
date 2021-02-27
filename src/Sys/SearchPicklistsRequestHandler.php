<?php
declare(strict_types=1);

namespace Triniti\Sys;

use Gdbots\Ncr\AbstractSearchNodesRequestHandler;
use Gdbots\Pbj\Message;
use Gdbots\Pbjx\Pbjx;
use Triniti\Schemas\Sys\Request\SearchPicklistsResponseV1;

class SearchPicklistsRequestHandler extends AbstractSearchNodesRequestHandler
{
    public static function handlesCuries(): array
    {
        return ['triniti:sys:request:search-picklists-request'];
    }

    protected function createSearchNodesResponse(Message $request, Pbjx $pbjx): Message
    {
        return SearchPicklistsResponseV1::create();
    }
}
