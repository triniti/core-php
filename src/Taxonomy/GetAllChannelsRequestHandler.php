<?php
declare(strict_types=1);

namespace Triniti\Taxonomy;

use Gdbots\Pbj\Message;
use Gdbots\Pbj\MessageResolver;
use Gdbots\Pbjx\Pbjx;
use Gdbots\Pbjx\RequestHandler;
use Triniti\Schemas\Taxonomy\Request\SearchChannelsRequestV1;

/**
 * @deprecated will be removed in 3.x
 */
class GetAllChannelsRequestHandler implements RequestHandler
{
    public function handleRequest(Message $request, Pbjx $pbjx): Message
    {
        $response = $this->createGetAllChannelsResponse($request, $pbjx);
        $searchRequest = SearchChannelsRequestV1::create();

        try {
            $searchResponse = $pbjx->copyContext($request, $searchRequest)->request($searchRequest);
        } catch (\Throwable $e) {
            return $response;
        }

        $channels = $searchResponse->get('nodes', []);
        $refs = array_map(fn(Message $role) => $role->generateNodeRef(), $channels);

        $response->addToSet('channels', $refs);
    }

    /**
     * @param Message $request
     * @param Pbjx                  $pbjx
     *
     * @return Message
     */
    protected function createGetAllChannelsResponse(Message $request, Pbjx $pbjx): Message
    {
        $curie = MessageResolver::findOneUsingMixin('gdbots:taxonomy:mixin:get-all-channels-response:v1');
        return MessageResolver::resolveCurie($curie)::create();
    }

    /**
     * {@inheritdoc}
     */
    public static function handlesCuries(): array
    {
        return MessageResolver::findAllUsingMixin('gdbots:taxonomy:mixin:get-all-channels-request:v1', false);
    }
}
