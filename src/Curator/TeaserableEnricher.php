<?php
declare(strict_types=1);

namespace Triniti\Curator;

use Gdbots\Pbjx\DependencyInjection\PbjxEnricher;
use Gdbots\Pbjx\Event\PbjxEvent;
use Gdbots\Pbjx\EventSubscriber;

final class TeaserableEnricher implements EventSubscriber, PbjxEnricher
{
    public static function getSubscribedEvents()
    {
        return [
            'triniti:curator:mixin:teaser.enrich'     => 'enrichWithOrderDate',
            'triniti:curator:mixin:teaserable.enrich' => 'enrichWithOrderDate',
        ];
    }

    /**
     * Ensures that a teaserable node always has its order_date
     * field populated.  This is important because we use this
     * date for sorting virtually all lists of content on the site.
     *
     * @param PbjxEvent $pbjxEvent
     */
    public function enrichWithOrderDate(PbjxEvent $pbjxEvent): void
    {
        $node = $pbjxEvent->getMessage();
        if ($node->isFrozen()) {
            return;
        }

        if ($node->has('order_date')) {
            return;
        }

        if ($pbjxEvent->hasParentEvent()) {
            $parentEvent = $pbjxEvent->getParentEvent()->getMessage();
            if (!$parentEvent::schema()->hasMixin('gdbots:pbjx:mixin:event')) {
                return;
            }
        }

        $node->set('order_date', $node->get('created_at')->toDateTime());
    }
}
