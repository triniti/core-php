<?php
declare(strict_types=1);

namespace Triniti\News;

use Gdbots\Ncr\AbstractSearchNodesRequestHandler;
use Gdbots\Ncr\Ncr;
use Gdbots\Ncr\NcrSearch;
use Gdbots\Pbj\Message;
use Gdbots\Pbj\MessageResolver;
use Gdbots\Pbj\SchemaQName;
use Gdbots\Pbj\WellKnown\NodeRef;
use Gdbots\Pbjx\Pbjx;
use Gdbots\QueryParser\Enum\BoolOperator;
use Gdbots\QueryParser\Node\Field;
use Gdbots\QueryParser\Node\Word;
use Gdbots\QueryParser\ParsedQuery;
use Gdbots\QueryParser\QueryParser;
use Gdbots\Schemas\Common\Enum\Trinary;
use Gdbots\Schemas\Ncr\Enum\NodeStatus;
use Psr\Cache\CacheItemPoolInterface;
use Triniti\Schemas\News\Enum\SearchArticlesSort;

class SearchArticlesRequestHandler extends AbstractSearchNodesRequestHandler
{
    protected const SLOTTING_MAX = 15;
    protected const SLOTTING_TTL = 180;

    protected Ncr $ncr;
    protected CacheItemPoolInterface $cache;

    public static function handlesCuries(): array
    {
        // deprecated mixins, will be removed in 3.x
        $curies = MessageResolver::findAllUsingMixin('triniti:news:mixin:search-articles-request:v1', false);
        $curies[] = 'triniti:news:request:search-articles-request';
        return $curies;
    }

    public function __construct(NcrSearch $ncrSearch, Ncr $ncr, CacheItemPoolInterface $cache)
    {
        parent::__construct($ncrSearch);
        $this->ncr = $ncr;
        $this->cache = $cache;
    }

    public function handleRequest(Message $request, Pbjx $pbjx): Message
    {
        $sort = $request->get('sort');
        if (SearchArticlesSort::POPULARITY()->equals($sort) || SearchArticlesSort::COMMENTS()->equals($sort)) {
            return $this->handleUsingStats($request, $pbjx);
        }

        try {
            $slottedNodes = $this->getSlottedNodes($request, $pbjx);
        } catch (\Throwable $e) {
            $slottedNodes = [];
        }

        if (empty($slottedNodes)) {
            return parent::handleRequest($request, $pbjx);
        }

        $request = clone $request;
        $count = $request->get('count');
        $request->set('count', $count + count($slottedNodes));
        $response = parent::handleRequest($request, $pbjx);

        $slottedIds = [];
        foreach ($slottedNodes as $slottedNode) {
            $slottedIds[$slottedNode->fget('_id')] = true;
        }

        /** @var Message[] $unslottedNodes */
        $unslottedNodes = $response->get('nodes', []);
        $response->clear('nodes');

        /** @var Message[] $finalNodes */
        $finalNodes = [];

        $page = $request->get('page');
        $slot = (($page - 1) * $count) + 1;
        $end = $slot + $count;

        for (; $slot < $end; $slot++) {
            if (isset($slottedNodes[$slot])) {
                $finalNodes[] = $slottedNodes[$slot];
                continue;
            }

            do {
                $node = array_shift($unslottedNodes);
                if (!isset($slottedIds[$node->fget('_id')])) {
                    $finalNodes[] = $node;
                    break;
                }
            } while (null !== $node);
        }

        while (count($finalNodes) < $count) {
            $node = array_shift($unslottedNodes);
            if (null === $node) {
                break;
            }

            if (!isset($slottedIds[$node->fget('_id')])) {
                $finalNodes[] = $node;
            }
        }

        return $response->addToList('nodes', $finalNodes);
    }

    /**
     * Returns the slotted nodes for a given slotting_key.
     * The return array is keyed by the slot position it should occupy.
     *
     * @param Message $request
     * @param Pbjx               $pbjx
     *
     * @return Message[]
     */
    protected function getSlottedNodes(Message $request, Pbjx $pbjx): array
    {
        if (!$request->has('slotting_key') || NodeStatus::PUBLISHED !== $request->fget('status')) {
            return [];
        }

        $slottingKey = $request->get('slotting_key');
        $cacheKey = "news.slotting.{$slottingKey}.php";

        $cacheItem = $this->cache->getItem($cacheKey);
        if ($cacheItem->isHit()) {
            $cachedSlots = $cacheItem->get();
            if (is_array($cachedSlots)) {
                $nodeRefs = [];
                foreach ($cachedSlots as $nodeRefStr) {
                    $nodeRefs[] = NodeRef::fromString($nodeRefStr);
                }

                /** @var Message[] $slots */
                $slots = [];
                $nodes = $this->ncr->getNodes($nodeRefs, false, ['causator' => $request]);

                foreach ($nodes as $node) {
                    if (
                        NodeStatus::PUBLISHED !== $node->fget('status')
                        || $node->get('is_unlisted', false)
                        || $node->get('is_locked', false)
                        || !$node->isInMap('slotting', $slottingKey)
                    ) {
                        continue;
                    }

                    $slot = (int)$node->getFromMap('slotting', $slottingKey);
                    if ($slot < 1 || $slot > self::SLOTTING_MAX || isset($slots[$slot])) {
                        continue;
                    }

                    $slots[$slot] = $node;
                }

                return $slots;
            }
        }

        $slottingMax = self::SLOTTING_MAX;
        $query = "+slotting.{$slottingKey}:[1..{$slottingMax}]";
        $parsedQuery = (new QueryParser())->parse($query);

        $slotRequest = $request::schema()->createMessage();
        $slotRequest
            ->set('q', $query)
            ->addToSet('fields_used', $parsedQuery->getFieldsUsed())
            ->set('parsed_query_json', json_encode($parsedQuery))
            ->set('sort', SearchArticlesSort::ORDER_DATE_DESC())
            ->set('status', NodeStatus::PUBLISHED())
            ->set('count', min($request->get('count'), self::SLOTTING_MAX))
            ->set('is_unlisted', Trinary::FALSE_VAL)
            ->set('is_locked', Trinary::FALSE_VAL);

        $response = $this->createSearchNodesResponse($slotRequest, $pbjx);
        $this->beforeSearchNodes($slotRequest, $parsedQuery);
        $qnames = $this->createQNamesForSearchNodes($request, $parsedQuery);

        $this->ncrSearch->searchNodes(
            $slotRequest,
            $parsedQuery,
            $response,
            $qnames,
            ['causator' => $request]
        );

        $slots = [];
        $slotsToCache = [];

        /** @var Message $node */
        foreach ($response->get('nodes', []) as $node) {
            $slot = (int)$node->getFromMap('slotting', $slottingKey);
            if (isset($slots[$slot])) {
                continue;
            }

            $slots[$slot] = $node;
            $slotsToCache[$slot] = NodeRef::fromNode($node)->toString();
        }

        $this->cache->saveDeferred($cacheItem->set($slotsToCache)->expiresAfter(self::SLOTTING_TTL));

        return $slots;
    }

    /**
     * When a search is sorted by a stat we currently only support page 1 and within
     * the range of content created within the last 3 days.  Reason being that
     * elasticsearch parent/child mappings must be created in the same put call but
     * our process does a separate mapping->send() for each type.  Bummer.  For
     * now this is sufficient as we only render articles using stats in widgets
     * with 5-10 articles.  On top of that, we usually want recent popularity
     * and not all time popularity.
     *
     * @param Message $request
     * @param Pbjx               $pbjx
     *
     * @return Message
     */
    protected function handleUsingStats(Message $request, Pbjx $pbjx): Message
    {
        $statsRequest = $request::schema()->createMessage();
        $statsRequest
            ->set('status', NodeStatus::PUBLISHED())
            // the article-stats created_after is derived
            // from the article published_at
            ->set('created_after', $request->get('created_after', new \DateTime('-3 days')))
            ->set('created_before', $request->get('created_before'))
            ->set('count', $request->get('count'))
            ->set('sort', $request->get('sort'));

        $vendor = MessageResolver::getDefaultVendor();
        $response = $this->createSearchNodesResponse($statsRequest, $pbjx);

        $this->ncrSearch->searchNodes(
            $statsRequest,
            new ParsedQuery(),
            $response,
            [SchemaQName::fromString("{$vendor}:article-stats")],
            ['causator' => $request]
        );

        if (!$response->has('nodes')) {
            return $response;
        }

        /** @var Message[] $statNodes */
        $statNodes = $response->get('nodes');
        $response
            ->clear('nodes')
            ->clear('total')
            ->clear('has_more');

        /** @var NodeRef[] $nodeRefs */
        $nodeRefs = [];
        foreach ($statNodes as $statNode) {
            $nodeRefs[] = NodeRef::fromString("{$vendor}:article:{$statNode->fget('_id')}");;
        }

        /** @var Message[] $finalNodes */
        $finalNodes = [];
        $nodes = $this->ncr->getNodes($nodeRefs, false, ['causator' => $request]);

        foreach ($nodeRefs as $nodeRef) {
            $key = $nodeRef->toString();
            if (!isset($nodes[$key])) {
                continue;
            }

            $node = $nodes[$key];

            if (
                !NodeStatus::PUBLISHED()->equals($node->get('status'))
                || $node->get('is_unlisted', false)
                || $node->get('is_locked', false)
            ) {
                continue;
            }

            $finalNodes[] = $node;
        }

        return $response->set('total', count($finalNodes))->addToList('nodes', $finalNodes);
    }

    protected function beforeSearchNodes(Message $request, ParsedQuery $parsedQuery): void
    {
        parent::beforeSearchNodes($request, $parsedQuery);
        $required = BoolOperator::REQUIRED();

        if ('home' === $request->get('slotting_key')
            && !$request->isInSet('fields_used', 'is_homepage_news')
        ) {
            $parsedQuery->addNode(new Field('is_homepage_news', new Word('true', $required), $required));
        }

        foreach (['is_unlisted', 'is_locked'] as $trinary) {
            if (Trinary::UNKNOWN !== $request->get($trinary)) {
                $parsedQuery->addNode(
                    new Field(
                        $trinary,
                        new Word(Trinary::TRUE_VAL === $request->get($trinary) ? 'true' : 'false', $required),
                        $required
                    )
                );
            }
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
        return MessageResolver::resolveCurie('*:news:request:search-articles-response:v1')::create();
    }
}
