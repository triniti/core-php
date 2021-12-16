<?php
declare(strict_types=1);

namespace Triniti\Ncr;

use Gdbots\Pbj\Message;
use Gdbots\Pbj\MessageResolver;
use Gdbots\Pbj\WellKnown\NodeRef;
use Gdbots\Pbjx\EventStore\StreamSlice;
use Gdbots\Pbjx\Pbjx;
use Gdbots\Pbjx\RequestHandler;
use Gdbots\Schemas\Pbjx\StreamId;

/**
 * @deprecated will be removed in 4.x
 */
class GetNodeHistoryRequestHandler implements RequestHandler
{
    public static function handlesCuries(): array
    {
        return MessageResolver::findAllUsingMixin('gdbots:pbjx:mixin:get-events-request:v1', false);
    }

    public function handleRequest(Message $request, Pbjx $pbjx): Message
    {
        $streamId = $this->deriveNewStreamId($request);
        $response = $this->createGetEventsResponse($request, $pbjx);
        $context = ['causator' => $request];

        // if someone is getting "creative" and trying to pull a different stream
        // then we'll just return an empty slice.  no soup for you.
        if ($this->canReadStream($request, $streamId, $pbjx)) {
            $slice = $pbjx->getEventStore()->getStreamSlice(
                $streamId,
                $request->get('since'),
                $request->get('count'),
                $request->get('forward'),
                true,
                $context
            );
        } else {
            $slice = new StreamSlice([], $streamId, $request->get('forward'));
        }

        return $response->set('has_more', $slice->hasMore())
            ->set('last_occurred_at', $slice->getLastOccurredAt())
            ->addToList('events', $slice->toArray()['events']);
    }

    protected function deriveNewStreamId(Message $request): StreamId
    {
        /** @var StreamId $streamId */
        $streamId = $request->get('stream_id');

        $vendor = MessageResolver::getDefaultVendor();
        $topic = str_replace('.history', '', $streamId->getVendor());
        $id = $streamId->getTopic();

        return StreamId::fromNodeRef(NodeRef::fromString("{$vendor}:{$topic}:{$id}"));
    }

    protected function canReadStream(Message $request, StreamId $streamId, Pbjx $pbjx): bool
    {
        /*
         * a simplistic but mostly correct assertion that requests tend
         * to be named "acme:news:request:get-[node-label]-history-request".
         * If the incoming request is asking for that topic it is allowed.
         *
         * This only exists to prevent someone with permission to get
         * history on one thing but pass in a different stream id
         * (message permission vs message content permission).
         *
         * Override if more complex check is desired or if the convention
         * doesn't match.
         */
        $allowedTopic = explode('-', $request::schema()->getCurie()->getMessage());
        array_shift($allowedTopic);
        array_pop($allowedTopic);
        array_pop($allowedTopic);
        $allowedTopic = implode('', $allowedTopic);

        // replace hero-bar-widget with just "widget"
        // or youtube-video-teaser with just "teaser"
        $parts = explode('-', $streamId->getTopic());
        $topic = array_pop($parts);

        return $allowedTopic === $topic;
    }

    protected function createGetEventsResponse(Message $request, Pbjx $pbjx): Message
    {
        $curie = str_replace('-request', '-response', $request::schema()->getCurie()->toString());
        return MessageResolver::resolveCurie($curie)::create();
    }
}
