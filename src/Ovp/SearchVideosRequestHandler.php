<?php
declare(strict_types=1);

namespace Triniti\Ovp;

use Gdbots\Ncr\AbstractSearchNodesRequestHandler;
use Gdbots\Pbj\Message;
use Gdbots\Pbj\MessageResolver;
use Gdbots\Pbj\WellKnown\NodeRef;
use Gdbots\Pbjx\Pbjx;
use Gdbots\QueryParser\Enum\BoolOperator;
use Gdbots\QueryParser\Node\Field;
use Gdbots\QueryParser\Node\Word;
use Gdbots\QueryParser\ParsedQuery;
use Gdbots\Schemas\Common\Enum\Trinary;
use Triniti\Schemas\Ovp\Request\SearchVideosResponseV1;

class SearchVideosRequestHandler extends AbstractSearchNodesRequestHandler
{
    public static function handlesCuries(): array
    {
        // deprecated mixins, will be removed in 3.x
        $curies = MessageResolver::findAllUsingMixin('triniti:news:mixin:search-videos-request:v1', false);
        $curies[] = 'triniti:news:request:search-videos-request';
        return $curies;
    }

    protected function beforeSearchNodes(Message $request, ParsedQuery $parsedQuery): void
    {
        parent::beforeSearchNodes($request, $parsedQuery);
        $required = BoolOperator::REQUIRED();

        if (Trinary::UNKNOWN !== $request->get('is_unlisted')) {
            $parsedQuery->addNode(
                new Field(
                    'is_unlisted',
                    new Word(Trinary::TRUE_VAL === $request->get('is_unlisted') ? 'true' : 'false', $required),
                    $required
                )
            );
        }

        if ($request->has('show')) {
            $parsedQuery->addNode(
                new Field(
                    'show',
                    new Word((string)$request->get('show'), $required),
                    $required
                )
            );
        }

        if ($request->has('channel_ref')) {
            $parsedQuery->addNode(
                new Field(
                    'channel_ref',
                    new Word((string)$request->get('channel_ref'), $required),
                    $required
                )
            );
        }

        /** @var NodeRef $nodeRef */
        foreach ($request->get('category_refs', []) as $nodeRef) {
            $parsedQuery->addNode(
                new Field(
                    'category_refs',
                    new Word($nodeRef->toString(), $required),
                    $required
                )
            );
        }

        foreach ($request->get('person_refs', []) as $nodeRef) {
            $parsedQuery->addNode(
                new Field(
                    'person_refs',
                    new Word($nodeRef->toString(), $required),
                    $required
                )
            );
        }
    }

    protected function createSearchNodesResponse(Message $request, Pbjx $pbjx): Message
    {
        return SearchVideosResponseV1::create();
    }
}