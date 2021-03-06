<?php
declare(strict_types=1);

namespace Triniti\Taxonomy;

use Gdbots\Ncr\AbstractSearchNodesRequestHandler;
use Gdbots\Pbj\Message;
use Gdbots\Pbjx\Pbjx;
use Triniti\Schemas\Taxonomy\Request\SearchChannelsResponseV1;

class SearchChannelsRequestHandler extends AbstractSearchNodesRequestHandler
{
    public static function handlesCuries(): array
    {
        return ['triniti:taxonomy:request:search-channels-request'];
    }

    protected function createSearchNodesResponse(Message $request, Pbjx $pbjx): Message
    {
        return SearchChannelsResponseV1::create();
    }
}
