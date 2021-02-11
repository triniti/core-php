<?php
declare(strict_types=1);

namespace Triniti\Tests\Dam;

use Gdbots\Ncr\NcrSearch;
use Gdbots\Pbj\Message;
use Gdbots\Pbj\SchemaQName;
use Gdbots\Pbj\WellKnown\NodeRef;
use Gdbots\QueryParser\ParsedQuery;

class MockNcrSearch implements NcrSearch
{
    private array $nodes = [];

    public function createStorage(SchemaQName $qname, array $context = []): void
    {
        var_dump('MockNcrSearch createStorage');
        die();
        // do nothing
    }

    public function describeStorage(SchemaQName $qname, array $context = []): string
    {
        var_dump('MockNcrSearch describeStorage');
        die();
        // do nothing
    }

    public function indexNodes(array $nodes, array $context = []): void
    {
        $this->nodes = array_merge($this->nodes, $nodes);
    }

    public function hasIndexedNode(NodeRef $nodeRef): bool
    {
        for ($i = 0; $i < count($this->nodes); $i++) {
            if ($this->nodes[$i]->generateNodeRef()->equals($nodeRef)) {
                return true;
            }
        }
        return false;
    }

    public function deleteNodes(array $nodeRefs, array $context = []): void
    {
        var_dump('MockNcrSearch deleteNodes');
        die();
        // do nothing
    }

    public function searchNodes(Message $request, ParsedQuery $parsedQuery, Message $response, array $qnames = [], array $context = []): void
    {
        $response->addToList('nodes', $this->nodes);
    }

}
