<?php
declare(strict_types=1);

namespace Triniti\Curator;

use Gdbots\Ncr\IndexQueryBuilder;
use Gdbots\Ncr\Ncr;
use Gdbots\Pbj\SchemaQName;
use Gdbots\Pbj\WellKnown\NodeRef;
use Gdbots\Schemas\Ncr\Enum\NodeStatus;

trait SyncTeaserTrait
{
    protected Ncr $ncr;

    protected function getTeasers(NodeRef $targetRef, ?NodeStatus $status = null): array
    {
        $qname = SchemaQName::fromString("{$targetRef->getVendor()}:{$targetRef->getLabel()}-teaser");
        $nodes = [];
        $cursor = null;

        do {
            $builder = IndexQueryBuilder::create($qname, 'target', $targetRef->toString())
                ->setCursor($cursor)
                ->sortAsc(false);

            if (null === $status) {
                $builder->filterNe('status', NodeStatus::DELETED);
            } else {
                $builder->filterEq('status', $status->value);
            }

            $result = $this->ncr->findNodeRefs($builder->build());
            $nodes = array_merge($nodes, $this->ncr->getNodes($result->getNodeRefs(), true));
            $cursor = $result->getNextCursor();
        } while ($result->hasMore());

        $teasers = ['all' => [], 'sync' => []];
        foreach ($nodes as $node) {
            $teasers['all'][] = $node;
            if ($node->get('sync_with_target')) {
                $teasers['sync'][] = $node;
            }
        }

        return $teasers;
    }
}
