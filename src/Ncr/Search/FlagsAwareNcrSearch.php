<?php
declare(strict_types=1);

namespace Triniti\Ncr\Search;

use Gdbots\Ncr\NcrSearch;
use Gdbots\Pbj\Message;
use Gdbots\Pbj\SchemaQName;
use Gdbots\QueryParser\ParsedQuery;
use Gdbots\Schemas\Common\Enum\Trinary;
use Triniti\Sys\Flags;

class FlagsAwareNcrSearch implements NcrSearch
{
    protected Flags $flags;
    protected NcrSearch $next;
    protected int $readOnly = Trinary::UNKNOWN;

    public function __construct(Flags $flags, NcrSearch $next)
    {
        $this->flags = $flags;
        $this->next = $next;
    }

    public function createStorage(SchemaQName $qname, array $context = []): void
    {
        $this->next->createStorage($qname, $context);
    }

    public function describeStorage(SchemaQName $qname, array $context = []): string
    {
        return $this->next->describeStorage($qname, $context);
    }

    public function indexNodes(array $nodes, array $context = []): void
    {
        if ($this->isReadOnly()) {
            return;
        }

        $this->next->indexNodes($nodes, $context);
    }

    public function deleteNodes(array $nodeRefs, array $context = []): void
    {
        if ($this->isReadOnly()) {
            return;
        }

        $this->next->deleteNodes($nodeRefs, $context);
    }

    public function searchNodes(Message $request, ParsedQuery $parsedQuery, Message $response, array $qnames = [], array $context = []): void
    {
        $this->next->searchNodes($request, $parsedQuery, $response, $qnames, $context);
    }

    protected function isReadOnly(): bool
    {
        if (Trinary::UNKNOWN === $this->readOnly) {
            if ('cli' === PHP_SAPI) {
                // allows us to run indexing when called via symfony console command
                // even when the ncr_indexing_disabled flag is true, unless an env
                // variable is present, in which case, still readonly.
                $this->readOnly = getenv('NCR_INDEXING_DISABLED') ? Trinary::TRUE_VAL : Trinary::FALSE_VAL;
            } else {
                $this->readOnly = $this->flags->getBoolean('ncr_indexing_disabled')
                    ? Trinary::TRUE_VAL
                    : Trinary::FALSE_VAL;
            }
        }

        return Trinary::TRUE_VAL === $this->readOnly;
    }
}
