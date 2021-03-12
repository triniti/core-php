<?php
declare(strict_types=1);

namespace Triniti\People;

use Gdbots\Pbjx\DependencyInjection\PbjxEnricher;
use Gdbots\Pbjx\Event\PbjxEvent;
use Gdbots\Pbjx\EventSubscriber;

final class HasPeopleEnricher implements EventSubscriber, PbjxEnricher
{
    public static function getSubscribedEvents()
    {
        return [
            'triniti:people:mixin:has-people.enrich' => 'enrich',
        ];
    }

    public function enrich(PbjxEvent $pbjxEvent): void
    {
        $node = $pbjxEvent->getMessage();
        if ($node->isFrozen() || !$node->has('primary_person_refs')) {
            return;
        }

        /*
         * ensure all primary refs are also in refs since
         * our application finds content based on person_refs.
         * primary is used for other purposes.
         */
        $node->addToSet(
            'person_refs',
            array_values($node->get('primary_person_refs'))
        );
    }
}
