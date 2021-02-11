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

class UnlinkAssetsHandler implements CommandHandler
{
    protected Ncr $ncr;

    public function __construct(Ncr $ncr)
    {
        $this->ncr = $ncr;
    }

    public static function handlesCuries(): array
    {
        // deprecated mixins, will be removed in 3.x
        $curies = MessageResolver::findAllUsingMixin('triniti:dam:mixin:unlink-assets', false);
        $curies[] = 'triniti:dam:command:unlink-assets';
        return $curies;
    }

    public function handleCommand(Message $command, Pbjx $pbjx): void
    {
        $nodeRef = $command->get('node_ref');
        /** @var NodeRef[] $assetRefs */
        $assetRefs = $command->get('asset_refs', []);

        static $validQNames = null;
        if (null === $validQNames) {
            $validQNames = [];
            foreach (MessageResolver::findAllUsingMixin('triniti:dam:mixin:asset:v1', false) as $curie) {
                $qname = SchemaCurie::fromString($curie)->getQName();
                $validQNames[$qname->getMessage()] = $qname;
            }
        }

        foreach ($assetRefs as $assetRef) {
            if (!isset($validQNames[$assetRef->getQName()->getMessage()])) {
                throw new AssetTypeNotSupported();
            }

            $asset = $this->ncr->getNode($assetRef, true);
            if (!$asset->isInSet('linked_refs', $nodeRef)) {
                continue;
            }

            $context = ['causator' => $command];
            $node = $this->ncr->getNode($assetRef, true, $context);
            /** @var AssetAggregate $aggregate */
            $aggregate = AggregateResolver::resolve($assetRef->getQName())::fromNode($node, $pbjx);
            $aggregate->sync();
            $aggregate->unlinkAsset($command);
            $aggregate->commit();
        }
    }
}
