<?php
declare(strict_types=1);

namespace Triniti\Tests;

use Gdbots\Ncr\Exception\NodeNotFound;
use Gdbots\Ncr\IndexQuery;
use Gdbots\Ncr\IndexQueryResult;
use Gdbots\Ncr\Ncr;
use Gdbots\Pbj\Message;
use Gdbots\Pbj\SchemaQName;
use Gdbots\Pbj\WellKnown\NodeRef;

/**
 * Naive alternative to the InMemoryNcr which does not handle DynamoDB aliasing in the same way.
 * Example: a query built using the alias 'target' when our search field is 'target_ref'.
 */
final class MockNcr implements Ncr {
    private array $nodes = [];

    public function createStorage(SchemaQName $qname, array $context = []): void
    {
        // TODO: Implement createStorage() method.
    }

    public function deleteNode(NodeRef $nodeRef, array $context = []): void
    {
        // TODO: Implement deleteNode() method.
    }

    public function describeStorage(SchemaQName $qname, array $context = []): string
    {
        // TODO: Implement describeStorage() method.
    }

    public function findNodeRefs(IndexQuery $query, array $context = []): IndexQueryResult
    {
        $queryMessage = $query->getQName()->getMessage();
        $nodeRefs = [];
        foreach ($this->nodes as $key => $value) {
            if ($queryMessage === $value::schema()->getQname()->getMessage()) {
                $nodeRefs[] = $key;
            }
        }
        return new IndexQueryResult(new IndexQuery(SchemaQName::fromString('a:b'), 'alias', 'value'), $nodeRefs);
    }

    public function getNode(NodeRef $nodeRef, bool $consistent = false, array $context = []): Message
    {
        if (!$this->hasNode($nodeRef)) {
            throw NodeNotFound::forNodeRef($nodeRef);
        }

        $node = $this->nodes[$nodeRef->toString()];
        if ($node->isFrozen()) {
            $node = $this->nodes[$nodeRef->toString()] = clone $node;
        }

        return $node;
    }

    public function getNodes(array $nodeRefs, bool $consistent = false, array $context = []): array
    {
        return array_filter($this->nodes, function (Message $node) {
            return $this->hasNode($node->generateNodeRef());
        });
    }

    public function hasNode(NodeRef $nodeRef, bool $consistent = false, array $context = []): bool
    {
        return isset($this->nodes[$nodeRef->toString()]);
    }

    public function pipeNodeRefs(SchemaQName $qname, array $context = []): \Generator
    {
        // TODO: Implement pipeNodeRefs() method.
    }

    public function pipeNodes(SchemaQName $qname, array $context = []): \Generator
    {
        // TODO: Implement pipeNodes() method.
    }

    public function putNode(Message $node, ?string $expectedEtag = null, array $context = []): void
    {
        $this->nodes[$node->generateNodeRef()->toString()] = $node;
    }
}
