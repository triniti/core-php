<?php
declare(strict_types=1);

namespace Triniti\Sys;

use Gdbots\Ncr\Ncr;
use Gdbots\Pbj\Message;
use Gdbots\Pbj\MessageResolver;
use Gdbots\Pbj\SchemaQName;
use Gdbots\Pbjx\Pbjx;
use Gdbots\Pbjx\RequestHandler;
use Triniti\Schemas\Sys\Mixin\Flagset\FlagsetV1Mixin;
use Triniti\Schemas\Sys\Request\SearchFlagsetsRequestV1;

class ListAllFlagsetsRequestHandler implements RequestHandler
{
    protected Ncr $ncr;

    public function __construct(Ncr $ncr)
    {
        $this->ncr = $ncr;
    }

    public function handleRequest(Message $request, Pbjx $pbjx): Message
    {
        $response = $this->createListAllFlagsetsResponse();

        $searchRequest = SearchFlagsetsRequestV1::create();

        try {
            $searchResponse = $pbjx->copyContext($request, $searchRequest)->request($searchRequest);
        } catch (\Throwable $e) {
            return $response;
        }

        $flagsets = $searchResponse->get('nodes', []);
        $refs = array_map(fn(Message $flagset) => $flagset->generateNodeRef(), $flagsets);

        return $response->addToSet('flagsets', $refs);
    }

    protected function createListAllFlagsetsResponse(): Message
    {
        static $listAllFlagsetsResponseClass = null;
        if (null === $listAllFlagsetsResponseClass) {
            $listAllFlagsetsResponseClass = MessageResolver::resolveCurie('*:sys:request:list-all-flagsets-response');
        }
        return $listAllFlagsetsResponseClass::create();
    }

    public static function handlesCuries(): array
    {
        return MessageResolver::findAllUsingMixin('triniti:sys:mixin:list-all-flagsets-request:v1');
    }
}
