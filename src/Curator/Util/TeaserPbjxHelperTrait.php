<?php
declare(strict_types=1);

namespace Triniti\Curator\Util;

use Gdbots\Pbj\MessageResolver;
use Gdbots\Pbj\SchemaCurie;
use Gdbots\Schemas\Ncr\Mixin\Node\Node;
use Gdbots\Schemas\Pbjx\Mixin\Command\Command;
use Gdbots\Schemas\Pbjx\Mixin\Event\Event;
use Triniti\Schemas\Curator\Mixin\Teaser\Teaser;

trait TeaserPbjxHelperTrait
{
    /**
     * @param Node $node
     *
     * @return bool
     */
    protected function isNodeSupported(Node $node): bool
    {
        return $node instanceof Teaser;
    }

    /**
     * @param Command $command
     * @param string  $suffix
     *
     * @return Event
     */
    protected function createEventFromCommand(Command $command, string $suffix): Event
    {
        $curie = $command::schema()->getCurie();
        $eventCurie = "{$curie->getVendor()}:{$curie->getPackage()}:event:teaser-{$suffix}";

        /** @var Event $class */
        $class = MessageResolver::resolveCurie(SchemaCurie::fromString($eventCurie));
        return $class::create();
    }
}