<?php
declare(strict_types=1);

namespace Triniti\Sys;

use Gdbots\Ncr\AbstractSearchNodesRequestHandler;
use Gdbots\Pbj\Message;
use Gdbots\Pbj\MessageResolver;
use Gdbots\Pbjx\Pbjx;
use Triniti\Schemas\Sys\Request\SearchRedirectsResponseV1;

class SearchRedirectsRequestHandler extends AbstractSearchNodesRequestHandler
{
    public static function handlesCuries(): array
    {
        // deprecated mixins, will be removed in 3.x
        $curies = MessageResolver::findAllUsingMixin('triniti:sys:mixin:search-redirects-request:v1', false);
        $curies[] = 'triniti:sys:request:search-redirects-request';
        return $curies;
    }

    protected function createSearchNodesResponse(Message $request, Pbjx $pbjx): Message
    {
        return SearchRedirectsResponseV1::create();
    }
}
