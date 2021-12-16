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

    public static function handlesCuries(): array
    {
        // deprecated mixins, will be removed in 4.x
        $curies = MessageResolver::findAllUsingMixin('triniti:dam:mixin:reorder-gallery-assets:v1', false);
        $curies[] = 'triniti:dam:command:reorder-gallery-assets';
        return $curies;
    }

    public function __construct(Ncr $ncr)
    {
        $this->ncr = $ncr;
    }

    public function handleCommand(Message $command, Pbjx $pbjx): void
    {
        $vendor = MessageResolver::getDefaultVendor();
        $context = ['causator' => $command];

        foreach ($command->get('gallery_seqs', []) as $id => $seq) {
            $assetId = AssetId::fromString($id);
            $nodeRef = NodeRef::fromString("{$vendor}:{$assetId->getType()}-asset:{$id}");
            $node = $this->ncr->getNode($nodeRef, true, $context);
            /** @var AssetAggregate $aggregate */
            $aggregate = AggregateResolver::resolve($nodeRef->getQName())::fromNode($node, $pbjx);
            $aggregate->sync($context);
            $aggregate->reorderGalleryAsset($command, $seq);
            $aggregate->commit($context);
        }
    }
}
