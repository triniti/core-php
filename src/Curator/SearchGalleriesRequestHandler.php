<?php
declare(strict_types=1);

namespace Triniti\Curator;

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

class SearchGalleriesRequestHandler extends AbstractSearchNodesRequestHandler
{
    public static function handlesCuries(): array
    {
        // deprecated mixins, will be removed in 3.x
        $curies = MessageResolver::findAllUsingMixin('triniti:curator:mixin:search-galleries-request:v1', false);
        $curies[] = 'triniti:curator:request:search-galleries-request';
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
        return MessageResolver::resolveCurie('*:curator:request:search-galleries-response:v1')::create();
    }
}
