<?php
declare(strict_types=1);

namespace Triniti\Sys;

use Gdbots\Ncr\Ncr;
use Gdbots\Pbj\Message;
use Gdbots\Pbj\MessageResolver;
use Gdbots\Pbj\SchemaQName;
use Gdbots\Pbjx\Pbjx;
use Gdbots\Pbjx\RequestHandler;
use Triniti\Schemas\Sys\Mixin\Picklist\PicklistV1Mixin;
use Triniti\Schemas\Sys\Request\SearchPicklistsRequestV1;

class ListAllPicklistsRequestHandler implements RequestHandler
{
    protected Ncr $ncr;
    public function __construct(Ncr $ncr)
    {
        $this->ncr = $ncr;
    }

    public function handleRequest(Message $request, Pbjx $pbjx): Message
    {
        $response = $this->createListAllPicklistsResponse();
        $searchRequest = SearchPicklistsRequestV1::create();

        try {
            $searchResponse = $pbjx->copyContext($request, $searchRequest)->request($searchRequest);
        } catch (\Throwable $e) {
            return $response;
        }

        $picklists = $searchResponse->get('nodes', []);
        $refs = array_map(fn(Message $picklist) => $picklist->generateNodeRef(), $picklists);
        return $response->addToSet('picklists', $refs);
    }

    protected function createListAllPicklistsResponse(): Message
    {
        static $listAllPicklistsResponseClass = null;
        if (null === $listAllPicklistsResponseClass){
            $listAllPicklistsResponseClass = MessageResolver::resolveCurie('*:sys:request:list-all-picklists-response');
        }
        return $listAllPicklistsResponseClass::create();
    }

    /**
     * {@inheritdoc}
     */
    public static function handlesCuries(): array
    {
        return MessageResolver::findAllUsingMixin('triniti:sys:mixin:list-all-picklists-request:v1');
    }
}
