<?php
declare(strict_types=1);

namespace Triniti\Sys;

use Gdbots\Pbj\Message;
use Gdbots\Pbj\MessageResolver;
use Gdbots\Pbjx\Pbjx;
use Gdbots\Pbjx\RequestHandler;
use Triniti\Schemas\Sys\Request\SearchPicklistsRequestV1;

/**
 * @deprecated will be removed in 4.x
 */
class ListAllPicklistsRequestHandler implements RequestHandler
{
    public static function handlesCuries(): array
    {
        return MessageResolver::findAllUsingMixin('triniti:sys:mixin:list-all-picklists-request:v1', false);
    }

    public function handleRequest(Message $request, Pbjx $pbjx): Message
    {
        $response = $this->createListAllPicklistsResponse($request, $pbjx);
        $searchRequest = SearchPicklistsRequestV1::create()->set('count', 255);

        do {
            try {
                $searchResponse = $pbjx->copyContext($request, $searchRequest)->request($searchRequest);
            } catch (\Throwable $e) {
                return $response;
            }

            $nodes = $searchResponse->get('nodes', []);
            $refs = array_map(fn(Message $node) => $node->generateNodeRef(), $nodes);
            $response->addToSet('picklists', $refs);
            $searchRequest = (clone $searchRequest)->set('page', $searchRequest->get('page') + 1);
        } while ($searchResponse->get('has_more'));

        return $response;
    }

    protected function createListAllPicklistsResponse(Message $request, Pbjx $pbjx): Message
    {
        return MessageResolver::resolveCurie('*:sys:request:list-all-picklists-response:v1')::create();
    }
}
