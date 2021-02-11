<?php
declare(strict_types=1);

namespace Triniti\Tests\AppleNews;

use Gdbots\Ncr\NcrSearch;
use Gdbots\Pbj\Message;
use Gdbots\Pbj\SchemaQName;
use Gdbots\QueryParser\ParsedQuery;

class MockNcrSearch implements NcrSearch
{
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
        // do nothing
    }

    public function deleteNodes(array $nodeRefs, array $context = []): void
    {
        // do nothing
    }

    public function searchNodes(Message $request, ParsedQuery $parsedQuery, Message $response, array $qnames = [], array $context = []): void {
        // do nothing
    }
}
