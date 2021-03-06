<?php
declare(strict_types=1);

namespace Triniti\Tests\Sys;

use Acme\Schemas\Sys\Command\CreateRedirectV1;
use Acme\Schemas\Sys\Command\UpdateRedirectV1;
use Acme\Schemas\Sys\Node\RedirectV1;
use Gdbots\Pbj\WellKnown\NodeRef;
use Gdbots\Schemas\Ncr\Enum\NodeStatus;
use Triniti\Schemas\Sys\RedirectId;
use Triniti\Sys\RedirectAggregate;
use Triniti\Tests\AbstractPbjxTest;

final class RedirectAggregateTest extends AbstractPbjxTest
{
    public function testCreateAggregate(): void
    {
        $node = RedirectV1::create()
            ->set('_id', RedirectId::fromUri('/awesome-redirect-1'))
            ->set('title', 'not-awesome-title');
        $aggregate = RedirectAggregate::fromNode($node, $this->pbjx);
        $aggregateNode = $aggregate->getNode();
        $this->assertSame('/awesome-redirect-1', $aggregateNode->get('title'));
        $this->assertTrue(NodeStatus::PUBLISHED()->equals($aggregateNode->get('status')));
    }

    public function testCreateNode(): void
    {
        $node = RedirectV1::create()
            ->set('_id', RedirectId::fromUri('/awesome-redirect-2'))
            ->set('title', 'not-awesome-title');
        $aggregate = RedirectAggregate::fromNode($node, $this->pbjx);
        $aggregate->createNode(CreateRedirectV1::create()->set('node', $node));
        $events = $aggregate->getUncommittedEvents();
        $eventNode = $events[0]->get('node');
        $this->assertSame('/awesome-redirect-2', $eventNode->get('title'));
        $this->assertTrue(NodeStatus::PUBLISHED()->equals($eventNode->get('status')));
    }

    public function testUpdateNode(): void
    {
        $node = RedirectV1::create()
            ->set('_id', RedirectId::fromUri('/awesome-redirect-3'))
            ->set('title', 'not-awesome-title');
        $aggregate = RedirectAggregate::fromNode($node, $this->pbjx);
        $command = UpdateRedirectV1::create()
            ->set('node_ref', NodeRef::fromNode($node))
            ->set('new_node', (clone $node)->set('title', 'not-awesome-title'));
        $aggregate->updateNode($command);
        $events = $aggregate->getUncommittedEvents();
        $eventNode = $events[0]->get('new_node');
        $this->assertSame('/awesome-redirect-3', $eventNode->get('title'));
        $this->assertTrue(NodeStatus::PUBLISHED()->equals($eventNode->get('status')));
    }
}
