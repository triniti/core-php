<?php
declare(strict_types=1);

namespace Triniti\Notify;

use Gdbots\Ncr\AbstractSearchNodesRequestHandler;
use Gdbots\Pbj\Message;
use Gdbots\Pbj\MessageResolver;
use Gdbots\Pbj\SchemaCurie;
use Gdbots\Pbjx\Pbjx;
use Gdbots\QueryParser\Enum\BoolOperator;
use Gdbots\QueryParser\Node\Field;
use Gdbots\QueryParser\Node\Word;
use Gdbots\QueryParser\ParsedQuery;
use Triniti\Schemas\Notify\Request\SearchNotificationsResponseV1;

class SearchNotificationsRequestHandler extends AbstractSearchNodesRequestHandler
{
    public static function handlesCuries(): array
    {
        // deprecated mixins, will be removed in 3.x
        $curies = MessageResolver::findAllUsingMixin('triniti:notify:mixin:search-notifications-request', false);
        $curies[] = 'triniti:notify:request:search-notifications-request';
        return $curies;
    }

    protected function createQNamesForSearchNodes(Message $request, ParsedQuery $parsedQuery): array
    {
        static $validQNames = null;
        if (null === $validQNames) {
            $validQNames = [];
            foreach (MessageResolver::findAllUsingMixin('triniti:notify:mixin:notification:v1', false) as $curie) {
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
        $required = BoolOperator::REQUIRED();

        if ($request->has('app_ref')) {
            $parsedQuery->addNode(
                new Field(
                    'app_ref',
                    new Word((string)$request->get('app_ref'), $required),
                    $required
                )
            );
        }

        if ($request->has('content_ref')) {
            $parsedQuery->addNode(
                new Field(
                    'content_ref',
                    new Word((string)$request->get('content_ref'), $required),
                    $required
                )
            );
        }

        if ($request->has('send_status')) {
            $parsedQuery->addNode(
                new Field(
                    'send_status',
                    new Word((string)$request->get('send_status'), $required),
                    $required
                )
            );
        }
    }

    protected function createSearchNodesResponse(Message $request, Pbjx $pbjx): Message
    {
        return SearchNotificationsResponseV1::create();
    }
}
