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

    public static function handlesCuries(): array
    {
        // deprecated mixins, will be removed in 4.x
        $curies = MessageResolver::findAllUsingMixin('triniti:dam:mixin:unlink-assets:v1', false);
        $curies[] = 'triniti:dam:command:unlink-assets';
        return $curies;
    }

    public function __construct(Ncr $ncr)
    {
        $this->ncr = $ncr;
    }

    public function handleCommand(Message $command, Pbjx $pbjx): void
    {
        $validQNames = [];
        foreach (MessageResolver::findAllUsingMixin('triniti:dam:mixin:asset:v1', false) as $curie) {
            $qname = SchemaCurie::fromString($curie)->getQName();
            $validQNames[$qname->toString()] = true;
        }

        $context = ['causator' => $command];

        /** @var NodeRef $assetRef */
        foreach ($command->get('asset_refs', []) as $assetRef) {
            if (!isset($validQNames[$assetRef->getQName()->toString()])) {
                throw new AssetTypeNotSupported();
            }

            $asset = $this->ncr->getNode($assetRef, true, $context);
            /** @var AssetAggregate $aggregate */
            $aggregate = AggregateResolver::resolve($assetRef->getQName())::fromNode($asset, $pbjx);
            $aggregate->sync($context);
            $aggregate->unlinkAsset($command);
            $aggregate->commit($context);
        }
    }
}
