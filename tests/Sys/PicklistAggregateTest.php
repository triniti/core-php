<?php
declare(strict_types=1);

namespace Triniti\Tests\Sys;

use Acme\Schemas\Sys\Command\CreatePicklistV1;
use Acme\Schemas\Sys\Command\UpdateFlagsetV1;
use Acme\Schemas\Sys\Command\UpdatePicklistV1;
use Acme\Schemas\Sys\Node\PicklistV1;
use Gdbots\Pbj\WellKnown\NodeRef;
use Gdbots\Schemas\Ncr\Enum\NodeStatus;
use Triniti\Schemas\Sys\PicklistId;
use Triniti\Sys\PicklistAggregate;
use Triniti\Tests\AbstractPbjxTest;

final class PicklistAggregateTest extends AbstractPbjxTest
{
    public function testCreateAggregate(): void
    {
        $node = PicklistV1::create()
            ->set('_id', PicklistId::fromString('awesome-picklist-1'))
            ->set('title', 'not-awesome-title');
        $aggregate = PicklistAggregate::fromNode($node, $this->pbjx);
        $aggregateNode = $aggregate->getNode();
        $this->assertSame('awesome-picklist-1', $aggregateNode->get('title'));
        $this->assertTrue(NodeStatus::PUBLISHED()->equals($aggregateNode->get('status')));
    }

    public function testCreateNode(): void
    {
        $node = PicklistV1::create()
            ->set('_id', PicklistId::fromString('awesome-picklist-2'))
            ->set('title', 'not-awesome-title');
        $aggregate = PicklistAggregate::fromNode($node, $this->pbjx);
        $aggregate->createNode(CreatePicklistV1::create()->set('node', $node));
        $events = $aggregate->getUncommittedEvents();
        $eventNode = $events[0]->get('node');
        $this->assertSame('awesome-picklist-2', $eventNode->get('title'));
        $this->assertTrue(NodeStatus::PUBLISHED()->equals($eventNode->get('status')));
    }

    public function testUpdateNode(): void
    {
        $node = PicklistV1::create()
            ->set('_id', PicklistId::fromString('awesome-picklist-3'))
            ->set('title', 'not-awesome-title');
        $aggregate = PicklistAggregate::fromNode($node, $this->pbjx);
        $command = UpdatePicklistV1::create()
            ->set('node_ref', NodeRef::fromNode($node))
            ->set('new_node', (clone $node)->set('title', 'not-awesome-title'));
        $aggregate->updateNode($command);
        $events = $aggregate->getUncommittedEvents();
        $eventNode = $events[0]->get('new_node');
        $this->assertSame('awesome-picklist-3', $eventNode->get('title'));
        $this->assertTrue(NodeStatus::PUBLISHED()->equals($eventNode->get('status')));
    }
}
