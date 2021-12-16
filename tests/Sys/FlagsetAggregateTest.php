<?php
declare(strict_types=1);

namespace Triniti\Tests\Sys;

use Acme\Schemas\Sys\Command\CreateFlagsetV1;
use Acme\Schemas\Sys\Command\UpdateFlagsetV1;
use Acme\Schemas\Sys\Node\FlagsetV1;
use Gdbots\Pbj\WellKnown\NodeRef;
use Gdbots\Schemas\Ncr\Enum\NodeStatus;
use Triniti\Schemas\Sys\FlagsetId;
use Triniti\Sys\FlagsetAggregate;
use Triniti\Tests\AbstractPbjxTest;

final class FlagsetAggregateTest extends AbstractPbjxTest
{
    public function testCreateAggregate(): void
    {
        $node = FlagsetV1::create()
            ->set('_id', FlagsetId::fromString('awesome-flagset-1'))
            ->set('title', 'not-awesome-title');
        $aggregate = FlagsetAggregate::fromNode($node, $this->pbjx);
        $aggregateNode = $aggregate->getNode();
        $this->assertSame('awesome-flagset-1', $aggregateNode->get('title'));
        $this->assertTrue(NodeStatus::PUBLISHED === $aggregateNode->get('status'));
    }

    public function testCreateNode(): void
    {
        $node = FlagsetV1::create()
            ->set('_id', FlagsetId::fromString('awesome-flagset-2'))
            ->set('title', 'not-awesome-title');
        $aggregate = FlagsetAggregate::fromNode($node, $this->pbjx);
        $aggregate->createNode(CreateFlagsetV1::create()->set('node', $node));
        $events = $aggregate->getUncommittedEvents();
        $eventNode = $events[0]->get('node');
        $this->assertSame('awesome-flagset-2', $eventNode->get('title'));
        $this->assertTrue(NodeStatus::PUBLISHED === $eventNode->get('status'));
    }

    public function testUpdateNode(): void
    {
        $node = FlagsetV1::create()
            ->set('_id', FlagsetId::fromString('awesome-flagset-3'))
            ->set('title', 'not-awesome-title');
        $aggregate = FlagsetAggregate::fromNode($node, $this->pbjx);
        $command = UpdateFlagsetV1::create()
            ->set('node_ref', NodeRef::fromNode($node))
            ->set('new_node', (clone $node)->set('title', 'not-awesome-title'));
        $aggregate->updateNode($command);
        $events = $aggregate->getUncommittedEvents();
        $eventNode = $events[0]->get('new_node');
        $this->assertSame('awesome-flagset-3', $eventNode->get('title'));
        $this->assertTrue(NodeStatus::PUBLISHED === $eventNode->get('status'));
    }
}
