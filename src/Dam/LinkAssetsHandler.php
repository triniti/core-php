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

class LinkAssetsHandler implements CommandHandler
{
    protected Ncr $ncr;

    public function __construct(Ncr $ncr)
    {
        $this->ncr = $ncr;
    }

    public static function handlesCuries(): array
    {
        // deprecated mixins, will be removed in 3.x
        $curies = MessageResolver::findAllUsingMixin('triniti:dam:mixin:link-assets', false);
        $curies[] = 'triniti:dam:command:link-assets';
        return $curies;
    }

    public function handleCommand(Message $command, Pbjx $pbjx): void
    {
        $nodeRef = $command->get('node_ref');
        $assetRefs = $command->get('asset_refs', []);

        static $validQNames = null;
        if (null === $validQNames) {
            $validQNames = [];
            foreach (MessageResolver::findAllUsingMixin('triniti:dam:mixin:asset:v1', false) as $curie) {
                $qname = SchemaCurie::fromString($curie)->getQName();
                $validQNames[$qname->getMessage()] = $qname;
            }
        }

        /** @var NodeRef[] $assetRefs */
        foreach ($assetRefs as $assetRef) {
            if (!isset($validQNames[$assetRef->getQName()->getMessage()])) {
                throw new AssetTypeNotSupported();
            }

            $asset = $this->ncr->getNode($assetRef, true);
            if ($asset->isInSet('linked_refs', $nodeRef)) {
                continue;
            }

            /** @var AssetAggregate $aggregate */
            $aggregate = AggregateResolver::resolve($assetRef->getQName())::fromNode($asset, $pbjx);
            $aggregate->sync(['causator' => $command]);
            $aggregate->linkAsset($command);
            $aggregate->commit();
        }
    }
}
