<?php
declare(strict_types=1);

namespace Triniti\Dam;

use Gdbots\Ncr\AggregateResolver;
use Gdbots\Ncr\Ncr;
use Gdbots\Pbj\Message;
use Gdbots\Pbj\MessageResolver;
use Gdbots\Pbj\WellKnown\NodeRef;
use Gdbots\Pbjx\CommandHandler;
use Gdbots\Pbjx\Pbjx;
use Triniti\Schemas\Dam\AssetId;

class ReorderGalleryAssetsHandler implements CommandHandler
{
    protected Ncr $ncr;

    public function __construct(Ncr $ncr)
    {
        $this->ncr = $ncr;
    }

    public static function handlesCuries(): array
    {
        // deprecated mixins, will be removed in 3.x
        $curies = MessageResolver::findAllUsingMixin('triniti:dam:mixin:reorder-gallery-assets', false);
        $curies[] = 'triniti:dam:command:reorder-gallery-assets';
        return $curies;
    }

    public function handleCommand(Message $command, Pbjx $pbjx): void
    {
        static $vendor = null;
        if (null === $vendor) {
            $vendor = MessageResolver::getDefaultVendor();
        }

        foreach ($command->get('gallery_seqs', []) as $id => $seq) {
            $assetId = AssetId::fromString($id);
            $nodeRef = NodeRef::fromString("{$vendor}:{$assetId->getType()}-asset:{$id}");
            $node = $this->ncr->getNode($nodeRef, true);
            /** @var AssetAggregate $aggregate */
            $aggregate = AggregateResolver::resolve($nodeRef->getQName())::fromNode($node, $pbjx);
            $aggregate->sync();
            $aggregate->reorderGalleryAsset($command);
            $aggregate->commit();
        }
    }
}
