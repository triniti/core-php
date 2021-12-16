<?php
declare(strict_types=1);

namespace Triniti\Curator;

use Gdbots\Ncr\AggregateResolver;
use Gdbots\Ncr\Ncr;
use Gdbots\Pbj\Message;
use Gdbots\Pbj\MessageResolver;
use Gdbots\Pbj\WellKnown\NodeRef;
use Gdbots\Pbjx\CommandHandler;
use Gdbots\Pbjx\Pbjx;
use Gdbots\Schemas\Ncr\Enum\NodeStatus;
use Triniti\Schemas\Dam\Request\SearchAssetsRequestV1;

class UpdateGalleryImageCountHandler implements CommandHandler
{
    protected Ncr $ncr;

    public static function handlesCuries(): array
    {
        // deprecated mixins, will be removed in 3.x
        $curies = MessageResolver::findAllUsingMixin('triniti:curator:mixin:update-gallery-image-count:v1', false);
        $curies[] = 'triniti:curator:command:update-gallery-image-count';
        return $curies;
    }

    public function __construct(Ncr $ncr)
    {
        $this->ncr = $ncr;
    }

    public function handleCommand(Message $command, Pbjx $pbjx): void
    {
        /** @var NodeRef $nodeRef */
        $nodeRef = $command->get('node_ref');
        $context = ['causator' => $command];

        $node = $this->ncr->getNode($nodeRef, true, $context);

        /** @var GalleryAggregate $aggregate */
        $aggregate = AggregateResolver::resolve($nodeRef->getQName())::fromNode($node, $pbjx);
        $aggregate->sync($context);
        $aggregate->updateGalleryImageCount($command, $this->getImageCount($command, $pbjx));
        $aggregate->commit($context);
    }

    protected function getImageCount(Message $command, Pbjx $pbjx): int
    {
        $request = SearchAssetsRequestV1::create()
            ->addToSet('types', ['image-asset'])
            ->set('count', 1)
            ->set('gallery_ref', $command->get('node_ref'))
            ->set('status', NodeStatus::PUBLISHED);

        try {
            return (int)$pbjx->copyContext($command, $request)->request($request)->get('total', 0);
        } catch (\Throwable $e) {
            return 0;
        }
    }
}
