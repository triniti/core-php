<?php
declare(strict_types=1);

namespace Triniti\Taxonomy;

use Gdbots\Pbj\Message;
use Gdbots\Pbj\MessageResolver;
use Gdbots\Pbjx\Pbjx;
use Gdbots\Pbjx\RequestHandler;

class SuggestHashtagsRequestHandler implements RequestHandler
{
    protected HashtagSuggester $suggester;

    public static function handlesCuries(): array
    {
        $curies = MessageResolver::findAllUsingMixin('triniti:taxonomy:mixin:suggest-hashtags-request:v1', false);
        $curies[] = 'triniti:taxonomy:request:suggest-hashtags-request';
        return $curies;
    }

    public function __construct(HashtagSuggester $suggester)
    {
        $this->suggester = $suggester;
    }

    public function handleRequest(Message $request, Pbjx $pbjx): Message
    {
        $response = $this->createSuggestHashtagsResponse($request, $pbjx);
        if (!$request->has('prefix')) {
            return $response;
        }

        $hashtags = $this->suggester->autocomplete(
            $request->get('prefix'),
            $request->get('count'),
            ['causator' => $request]
        );

        return $response->addToList('hashtags', $hashtags);
    }

    protected function createSuggestHashtagsResponse(Message $request, Pbjx $pbjx): Message
    {
        return MessageResolver::resolveCurie('*:taxonomy:request:suggest-hashtags-response:v1')::create();
    }
}
