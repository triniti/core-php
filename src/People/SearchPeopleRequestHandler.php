<?php
declare(strict_types=1);

namespace Triniti\People;

use Gdbots\Ncr\AbstractSearchNodesRequestHandler;
use Gdbots\Pbj\Message;
use Gdbots\Pbj\MessageResolver;
use Gdbots\Pbjx\Pbjx;
use Triniti\Schemas\People\Request\SearchPeopleResponseV1;

class SearchPeopleRequestHandler extends AbstractSearchNodesRequestHandler
{
    public static function handlesCuries(): array
    {
        // deprecated mixins, will be removed in 3.x
        $curies = MessageResolver::findAllUsingMixin('triniti:people:mixin:search-people-request:v1', false);
        $curies[] = 'triniti:people:request:search-people-request';
        return $curies;
    }

    protected function createSearchNodesResponse(Message $request, Pbjx $pbjx): Message
    {
        return SearchPeopleResponseV1::create();
    }
}
