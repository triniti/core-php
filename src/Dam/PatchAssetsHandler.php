<?php
declare(strict_types=1);

namespace Triniti\Dam;

use Gdbots\Ncr\AggregateResolver;
use Gdbots\Ncr\Ncr;
use Gdbots\Pbj\Message;
use Gdbots\Pbj\MessageResolver;
use Gdbots\Pbj\SchemaCurie;
use Gdbots\Pbj\WellKnown\NodeRef;
use Gdbots\Pbjx\CommandHandler;
use Gdbots\Pbjx\Pbjx;
use Triniti\Dam\Exception\AssetTypeNotSupported;

class PatchAssetsHandler implements CommandHandler
{
    protected Ncr $ncr;

    public static function handlesCuries(): array
    {
        // deprecated mixins, will be removed in 4.x
        $curies = MessageResolver::findAllUsingMixin('triniti:dam:mixin:patch-assets:v1', false);
        $curies[] = 'triniti:dam:command:patch-assets';
        return $curies;
    }

    public function __construct(Ncr $ncr)
    {
        $this->ncr = $ncr;
    }

    public function handleCommand(Message $command, Pbjx $pbjx): void
    {
        if (!$command->has('node_refs') || !$command->has('paths')) {
            // patching nothing can be ignored
            return;
        }

        $validQNames = [];
        foreach (MessageResolver::findAllUsingMixin('triniti:dam:mixin:asset:v1', false) as $curie) {
            $qname = SchemaCurie::fromString($curie)->getQName();
            $validQNames[$qname->toString()] = true;
        }

        /** @var NodeRef[] $nodeRefs */
        $nodeRefs = $command->get('node_refs');
        $context = ['causator' => $command];

        foreach ($nodeRefs as $nodeRef) {
            if (!isset($validQNames[$nodeRef->getQName()->toString()])) {
                throw new AssetTypeNotSupported();
            }

            $node = $this->ncr->getNode($nodeRef, true, $context);
            /** @var AssetAggregate $aggregate */
            $aggregate = AggregateResolver::resolve($nodeRef->getQName())::fromNode($node, $pbjx);
            $aggregate->sync($context);
            $aggregate->patchAsset($command);
            $aggregate->commit($context);
        }
    }
}
