<?php
declare(strict_types=1);

namespace Triniti\Dam;

use Gdbots\Ncr\AbstractSearchNodesRequestHandler;
use Gdbots\Pbj\Message;
use Gdbots\Pbj\MessageResolver;
use Gdbots\Pbj\SchemaCurie;
use Gdbots\Pbj\WellKnown\NodeRef;
use Gdbots\Pbjx\Pbjx;
use Gdbots\QueryParser\Enum\BoolOperator;
use Gdbots\QueryParser\Enum\ComparisonOperator;
use Gdbots\QueryParser\Node\Field;
use Gdbots\QueryParser\Node\NumberRange;
use Gdbots\QueryParser\Node\Numbr;
use Gdbots\QueryParser\Node\Word;
use Gdbots\QueryParser\ParsedQuery;

class SearchAssetsRequestHandler extends AbstractSearchNodesRequestHandler
{
    public static function handlesCuries(): array
    {
        // deprecated mixins, will be removed in 3.x
        $curies = MessageResolver::findAllUsingMixin('triniti:dam:mixin:search-assets-request:v1', false);
        $curies[] = 'triniti:dam:request:search-assets-request';
        return $curies;
    }

    protected function createQNamesForSearchNodes(Message $request, ParsedQuery $parsedQuery): array
    {
        static $validQNames = null;
        if (null === $validQNames) {
            $validQNames = [];
            foreach (MessageResolver::findAllUsingMixin('triniti:dam:mixin:asset:v1', false) as $curie) {
                $qname = SchemaCurie::fromString($curie)->getQName();
                $validQNames[$qname->getMessage()] = $qname;
            }
        }

        $qnames = [];
        foreach ($request->get('types', []) as $type) {
            if (isset($validQNames[$type])) {
                $qnames[] = $validQNames[$type];
            }
        }

        if (empty($qnames)) {
            $qnames = array_values($validQNames);
        }

        return $qnames;
    }

    protected function beforeSearchNodes(Message $request, ParsedQuery $parsedQuery): void
    {
        parent::beforeSearchNodes($request, $parsedQuery);
        $required = BoolOperator::REQUIRED;

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

        if ($request->has('linked_ref')) {
            $parsedQuery->addNode(
                new Field(
                    'linked_refs',
                    new Word((string)$request->get('linked_ref'), $required),
                    $required
                )
            );
        }

        if ($request->has('gallery_ref')) {
            $parsedQuery->addNode(
                new Field(
                    'gallery_ref',
                    new Word((string)$request->get('gallery_ref'), $required),
                    $required
                )
            );
        }

        if ($request->get('gallery_seq_min') > 0 && $request->get('gallery_seq_max') > 0) {
            $parsedQuery->addNode(
                new Field(
                    'gallery_seq',
                    new NumberRange(
                        new Numbr($request->get('gallery_seq_min')),
                        new Numbr($request->get('gallery_seq_max'))
                    ),
                    $required
                )
            );
        } elseif ($request->get('gallery_seq_min') > 0) {
            $parsedQuery->addNode(
                new Field(
                    'gallery_seq',
                    new Numbr($request->get('gallery_seq_min'), ComparisonOperator::GTE),
                    $required
                )
            );
        } elseif ($request->get('gallery_seq_max') > 0) {
            $parsedQuery->addNode(
                new Field(
                    'gallery_seq',
                    new Numbr($request->get('gallery_seq_max'), ComparisonOperator::LTE),
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
        return MessageResolver::resolveCurie('*:dam:request:search-assets-response:v1')::create();
    }
}
