<?php
declare(strict_types=1);

namespace Triniti\Canvas;

use Gdbots\Pbjx\DependencyInjection\PbjxEnricher;
use Gdbots\Pbjx\Event\PbjxEvent;
use Gdbots\Pbjx\EventSubscriber;

final class BlockEnricher implements EventSubscriber, PbjxEnricher
{
    public static function getSubscribedEvents()
    {
        return [
            'triniti:canvas:mixin:block.enrich' => ['enrichWithEtag', -5000],
        ];
    }

    /**
     * Blocks are value objects (they have no identity) but
     * they still benefit from having an etag as it makes
     * cache priming, invalidation, etc. simpler if
     * we don't have to calculate the hash and can just
     * ask the block for its etag.
     *
     * @param PbjxEvent $pbjxEvent
     */
    public function enrichWithEtag(PbjxEvent $pbjxEvent): void
    {
        $block = $pbjxEvent->getMessage();
        $block->set('etag', $block->generateEtag(['etag']));
    }
}
