<?php
declare(strict_types=1);

namespace Triniti\Taxonomy;

use Gdbots\Ncr\AbstractSearchNodesRequestHandler;
use Gdbots\Ncr\NcrSearch;
use Gdbots\Pbj\Message;
use Gdbots\Pbj\MessageResolver;
use Gdbots\Pbjx\Pbjx;
use Triniti\Schemas\Taxonomy\Request\SuggestHashtagsResponseV1;

class SuggestHashtagsRequestHandler extends AbstractSearchNodesRequestHandler
{
    protected HashtagSuggester $suggester;

    public function __construct(NcrSearch $ncrSearch, HashtagSuggester $suggester)
    {
        parent::__construct($ncrSearch);
        $this->suggester = $suggester;
    }

    public function handleRequest(Message $request, Pbjx $pbjx): Message
    {
        $response = $this->createSearchNodesResponse($request, $pbjx);
        if (!$request->has('prefix')) {
            return $response;
        }

        $hashtags = $this->suggester->autocomplete(
            $request->get('prefix'),
            $request->get('count'),
            $this->createNcrSearchContext($request)
        );

        return $response->addToList('hashtags', $hashtags);
    }

    protected function createNcrSearchContext(Message $request): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public static function handlesCuries(): array
    {
        $curies = MessageResolver::findAllUsingMixin('triniti:taxonomy:mixin:suggest-hashtags-request:v1', false);
        $curies[] = 'triniti:request:suggest-hashtags-request';
        return $curies;
    }

    protected function createSearchNodesResponse(Message $request, Pbjx $pbjx): Message
    {
        return SuggestHashtagsResponseV1::create();
    }

}
