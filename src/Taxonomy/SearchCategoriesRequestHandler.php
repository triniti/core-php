<?php
declare(strict_types=1);

namespace Triniti\Taxonomy;

use Gdbots\Ncr\AbstractSearchNodesRequestHandler;
use Gdbots\Pbj\Message;
use Gdbots\Pbj\MessageResolver;
use Gdbots\Pbjx\Pbjx;

class SearchCategoriesRequestHandler extends AbstractSearchNodesRequestHandler
{
    public static function handlesCuries(): array
    {
        // deprecated mixins, will be removed in 3.x
        $curies = MessageResolver::findAllUsingMixin('triniti:taxonomy:mixin:search-categories-request:v1', false);
        $curies[] = 'triniti:taxonomy:request:search-categories-request';
        return $curies;
    }

    protected function createSearchNodesResponse(Message $request, Pbjx $pbjx): Message
    {
        return MessageResolver::resolveCurie('*:taxonomy:request:search-categories-response:v1')::create();
    }
}
