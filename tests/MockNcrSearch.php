<?php
declare(strict_types=1);

namespace Triniti\Tests;

use Gdbots\Ncr\NcrSearch;
use Gdbots\Pbj\Message;
use Gdbots\Pbj\SchemaQName;
use Gdbots\Pbj\WellKnown\NodeRef;
use Gdbots\QueryParser\ParsedQuery;

class MockNcrSearch implements NcrSearch
{
    private array $nodes = [];

    public function hasIndexedNode(NodeRef $nodeRef): bool
    {
        for ($i = 0; $i < count($this->nodes); $i++) {
            if (NodeRef::fromNode($this->nodes[$i])->equals($nodeRef)) {
                return true;
            }
        }
        return false;
    }

    public function getNode(NodeRef $nodeRef): ?Message
    {
        if (!$this->hasIndexedNode($nodeRef)) {
            return null;
        }
        return array_filter($this->nodes, function (Message $node) use ($nodeRef) {
            return NodeRef::fromNode($node)->equals($nodeRef);
        })[0];
    }

    public function createStorage(SchemaQName $qname, array $context = []): void
    {
        // do nothing
    }

    public function describeStorage(SchemaQName $qname, array $context = []): string
    {
        // do nothing
    }

    public function indexNodes(array $nodes, array $context = []): void
    {
        $nodeRefs = array_map(function (Message $node) {
            return NodeRef::fromNode($node);
        }, $nodes);
        $this->deleteNodes($nodeRefs);
        $this->nodes = array_merge($this->nodes, $nodes);
    }

    public function deleteNodes(array $nodeRefs, array $context = []): void
    {
        foreach ($nodeRefs as $nodeRef) {
            $this->nodes = array_filter($this->nodes, function (Message $node) use ($nodeRef) {
                return !NodeRef::fromNode($node)->equals($nodeRef);
            });
        }
    }

    public function searchNodes(Message $request, ParsedQuery $parsedQuery, Message $response, array $qnames = [], array $context = []): void
    {
        $response->addToList('nodes', $this->nodes);
    }

}
