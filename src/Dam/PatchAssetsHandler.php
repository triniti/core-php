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

    public function __construct(Ncr $ncr)
    {
        $this->ncr = $ncr;
    }

    public static function handlesCuries(): array
    {
        // deprecated mixins, will be removed in 3.x
        $curies = MessageResolver::findAllUsingMixin('triniti:dam:mixin:patch-assets', false);
        $curies[] = 'triniti:dam:command:patch-assets';
        return $curies;
    }

    public function handleCommand(Message $command, Pbjx $pbjx): void
    {
        if (!$command->has('node_refs') || !$command->has('paths')) {
            return; // patching nothing can be ignored
        }

        /** @var NodeRef[] $nodeRefs */
        $nodeRefs = $command->get('node_refs');

        static $validQNames = null;
        if (null === $validQNames) {
            $validQNames = [];
            foreach (MessageResolver::findAllUsingMixin('triniti:dam:mixin:asset:v1', false) as $curie) {
                $qname = SchemaCurie::fromString($curie)->getQName();
                $validQNames[$qname->getMessage()] = $qname;
            }
        }

        foreach ($nodeRefs as $nodeRef) {
            if (!isset($validQNames[$nodeRef->getQName()->getMessage()])) {
                throw new AssetTypeNotSupported();
            }

            $context = ['causator' => $command];
            $node = $this->ncr->getNode($nodeRef, true, $context);
            /** @var AssetAggregate $aggregate */
            $aggregate = AggregateResolver::resolve($nodeRef->getQName())::fromNode($node, $pbjx);
            $aggregate->sync();
            $aggregate->patchAsset($command);
            $aggregate->commit();
        }
    }
}
