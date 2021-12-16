<?php
declare(strict_types=1);

namespace Triniti\Curator;

use Gdbots\Ncr\AbstractSearchNodesRequestHandler;
use Gdbots\Pbj\Message;
use Gdbots\Pbj\MessageResolver;
use Gdbots\Pbj\SchemaCurie;
use Gdbots\Pbjx\Pbjx;
use Gdbots\QueryParser\ParsedQuery;

class SearchWidgetsRequestHandler extends AbstractSearchNodesRequestHandler
{
    public static function handlesCuries(): array
    {
        // deprecated mixins, will be removed in 4.x
        $curies = MessageResolver::findAllUsingMixin('triniti:curator:mixin:search-widgets-request:v1', false);
        $curies[] = 'triniti:curator:request:search-widgets-request';
        return $curies;
    }

    protected function createQNamesForSearchNodes(Message $request, ParsedQuery $parsedQuery): array
    {
        static $validQNames = null;
        if (null === $validQNames) {
            $validQNames = [];
            foreach (MessageResolver::findAllUsingMixin('triniti:curator:mixin:widget:v1', false) as $curie) {
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

    protected function createSearchNodesResponse(Message $request, Pbjx $pbjx): Message
    {
        return MessageResolver::resolveCurie('*:curator:request:search-widgets-response:v1')::create();
    }
}
