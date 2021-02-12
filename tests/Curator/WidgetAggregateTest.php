<?php
declare(strict_types=1);

namespace Triniti\Tests\Curator;

use Acme\Schemas\Curator\Command\CreateWidgetV1;
use Acme\Schemas\Curator\Command\UpdateWidgetV1;
use Acme\Schemas\Curator\Node\AdWidgetV1;
use Gdbots\Pbj\WellKnown\NodeRef;
use Gdbots\Schemas\Ncr\Enum\NodeStatus;
use Triniti\Curator\WidgetAggregate;
use Triniti\Tests\AbstractPbjxTest;

final class WidgetAggregateTest extends AbstractPbjxTest
{
    public function testCreateAggregate(): void
    {
        $node = AdWidgetV1::create();
        $aggregate = WidgetAggregate::fromNode($node, $this->pbjx);
        $this->assertTrue(NodeStatus::PUBLISHED()->equals($aggregate->getNode()->get('status')));
    }

    public function testCreateNode(): void
    {
        $node = AdWidgetV1::create();
        $aggregate = WidgetAggregate::fromNode($node, $this->pbjx);
        $aggregate->createNode(CreateWidgetV1::create()->set('node', $node));
        $events = $aggregate->getUncommittedEvents();
        $this->assertTrue(NodeStatus::PUBLISHED()->equals($events[0]->get('node')->get('status')));
    }

    public function testUpdateNode(): void
    {
        $node = AdWidgetV1::create();
        $aggregate = WidgetAggregate::fromNode($node, $this->pbjx);
        $command = UpdateWidgetV1::create()
            ->set('node_ref', NodeRef::fromNode($node))
            ->set('new_node', (clone $node)->set('title', 'foo'));
        $aggregate->updateNode($command);
        $events = $aggregate->getUncommittedEvents();
        $this->assertTrue(NodeStatus::PUBLISHED()->equals($events[0]->get('new_node')->get('status')));
    }
}
