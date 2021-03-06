<?php
declare(strict_types=1);

namespace Triniti\Tests\Apollo;

use Acme\Schemas\Apollo\Event\PollCreatedV1;
use Acme\Schemas\Apollo\Event\PollDeletedV1;
use Acme\Schemas\Apollo\Event\PollExpiredV1;
use Acme\Schemas\Apollo\Event\PollMarkedAsDraftV1;
use Acme\Schemas\Apollo\Event\PollMarkedAsPendingV1;
use Acme\Schemas\Apollo\Event\PollPublishedV1;
use Acme\Schemas\Apollo\Event\PollScheduledV1;
use Acme\Schemas\Apollo\Event\PollUnpublishedV1;
use Acme\Schemas\Apollo\Event\PollUpdatedV1;
use Acme\Schemas\Apollo\Event\VoteCastedV1;
use Acme\Schemas\Apollo\Node\PollV1;
use Acme\Schemas\Apollo\PollAnswerV1;
use Gdbots\Ncr\Event\NodeProjectedEvent;
use Gdbots\Ncr\Exception\NodeNotFound;
use Gdbots\Ncr\Repository\InMemoryNcr;
use Gdbots\Pbj\WellKnown\Microtime;
use Gdbots\Pbj\WellKnown\NodeRef;
use Gdbots\Schemas\Ncr\Enum\NodeStatus;
use Triniti\Apollo\NcrPollStatsProjector;
use Triniti\Tests\AbstractPbjxTest;
use Triniti\Tests\MockNcrSearch;

final class NcrPollStatsProjectorTest extends AbstractPbjxTest
{
    private MockNcrSearch $ncrSearch;
    private NcrPollStatsProjector $projector;
    private InMemoryNcr $ncr;

    public function setup(): void
    {
        parent::setup();
        $this->ncrSearch = new MockNcrSearch();
        $this->ncr = new InMemoryNcr();
        $this->projector = new NcrPollStatsProjector($this->ncr, $this->ncrSearch);
    }

    protected function createStatsRef(NodeRef $pollRef): NodeRef
    {
        return NodeRef::fromString(str_replace('poll:', 'poll-stats:', $pollRef->toString()));
    }

    public function testNodeCreated(): void
    {
        $node = PollV1::create()->set('title', 'poll-title');
        $event = PollCreatedV1::create()->set('node', $node);
        $pbjxEvent = new NodeProjectedEvent($node, $event);
        $this->projector->onPollProjected($pbjxEvent);
        $statsRef = $this->createStatsRef(NodeRef::fromNode($node));
        $stats = $this->ncr->getNode($statsRef);

        $this->assertTrue($node->get('status')->equals($stats->get('status')));
        $this->assertSame((string)$node->get('_id'), (string)$stats->get('_id'));
        $this->assertSame($node->get('title'), $stats->get('title'));
        $this->assertSame((string)$node->get('created_at'), (string)$stats->get('created_at'));

        $this->assertTrue($this->ncrSearch->hasIndexedNode($statsRef));
    }

    public function testNodeCreatedAndDeleted(): void
    {
        $node = PollV1::create()->set('title', 'poll-title');
        $nodeRef = NodeRef::fromNode($node);
        $createdEvent = PollCreatedV1::create()->set('node', $node);
        $createdPbjxEvent = new NodeProjectedEvent($node, $createdEvent);
        $this->projector->onPollProjected($createdPbjxEvent);
        $statsRef = $this->createStatsRef(NodeRef::fromNode($node));
        $stats = $this->ncr->getNode($statsRef);

        $this->assertTrue($node->get('status')->equals($stats->get('status')));
        $this->assertSame((string)$node->get('_id'), (string)$stats->get('_id'));
        $this->assertSame($node->get('title'), $stats->get('title'));
        $this->assertSame((string)$node->get('created_at'), (string)$stats->get('created_at'));

        $this->assertTrue($this->ncrSearch->hasIndexedNode($statsRef));

        $deletedEvent = PollDeletedV1::create()->set('node_ref', $nodeRef);
        $deletedPbjxEvent = new NodeProjectedEvent((clone $node)->set('status', NodeStatus::DELETED()), $deletedEvent);
        $this->projector->onPollProjected($deletedPbjxEvent);

        $this->assertFalse($this->ncrSearch->hasIndexedNode($statsRef));
        $this->expectException(NodeNotFound::class);
        $this->ncr->getNode($statsRef);
    }

    public function testNodeExpired(): void
    {
        $node = PollV1::create()
            ->set('title', 'poll-title')
            ->set('status', NodeStatus::EXPIRED());
        $nodeRef = NodeRef::fromNode($node);
        $event = PollExpiredV1::create()->set('node_ref', $nodeRef);
        $createdPbjxEvent = new NodeProjectedEvent($node, $event);
        $this->projector->onPollProjected($createdPbjxEvent);
        $statsRef = $this->createStatsRef($nodeRef);
        $stats = $this->ncr->getNode($statsRef);

        $this->assertTrue(NodeStatus::EXPIRED()->equals($stats->get('status')));
        $this->assertTrue(NodeStatus::EXPIRED()->equals($this->ncrSearch->getNode($statsRef)->get('status')));
    }

    public function testNodeMarkedAsDraft(): void
    {
        $node = PollV1::create()->set('title', 'poll-title');
        $nodeRef = NodeRef::fromNode($node);
        $event = PollMarkedAsDraftV1::create()->set('node_ref', $nodeRef);
        $createdPbjxEvent = new NodeProjectedEvent($node, $event);
        $this->projector->onPollProjected($createdPbjxEvent);
        $statsRef = $this->createStatsRef($nodeRef);
        $stats = $this->ncr->getNode($statsRef);

        $this->assertTrue(NodeStatus::DRAFT()->equals($stats->get('status')));
        $this->assertTrue(NodeStatus::DRAFT()->equals($this->ncrSearch->getNode($statsRef)->get('status')));
    }

    public function testNodeMarkedAsPending(): void
    {
        $node = PollV1::create()
            ->set('title', 'poll-title')
            ->set('status', NodeStatus::PENDING());
        $nodeRef = NodeRef::fromNode($node);
        $event = PollMarkedAsPendingV1::create()->set('node_ref', $nodeRef);
        $createdPbjxEvent = new NodeProjectedEvent($node, $event);
        $this->projector->onPollProjected($createdPbjxEvent);
        $statsRef = $this->createStatsRef($nodeRef);
        $stats = $this->ncr->getNode($statsRef);

        $this->assertTrue(NodeStatus::PENDING()->equals($stats->get('status')));
        $this->assertTrue(NodeStatus::PENDING()->equals($this->ncrSearch->getNode($statsRef)->get('status')));
    }

    public function testNodePublished(): void
    {
        $publishedAt = new \DateTime();
        $node = PollV1::create()
            ->set('title', 'poll-title')
            ->set('status', NodeStatus::PUBLISHED())
            ->set('published_at', $publishedAt);
        $nodeRef = NodeRef::fromNode($node);
        $event = PollPublishedV1::create()
            ->set('node_ref', $nodeRef)
            ->set('published_at', $publishedAt);
        $createdPbjxEvent = new NodeProjectedEvent($node, $event);
        $this->projector->onPollProjected($createdPbjxEvent);
        $statsRef = $this->createStatsRef($nodeRef);
        $stats = $this->ncr->getNode($statsRef);

        $this->assertTrue(NodeStatus::PUBLISHED()->equals($stats->get('status')));
        $this->assertTrue(NodeStatus::PUBLISHED()->equals($this->ncrSearch->getNode($statsRef)->get('status')));
        $this->assertSame((string)$stats->get('created_at'), (string)Microtime::fromDateTime($publishedAt));
    }

    public function testNodeScheduled(): void
    {
        $publishAt = new \DateTime();
        $node = PollV1::create()
            ->set('title', 'poll-title')
            ->set('status', NodeStatus::SCHEDULED())
            ->set('published_at', $publishAt);
        $nodeRef = NodeRef::fromNode($node);
        $event = PollScheduledV1::create()
            ->set('node_ref', $nodeRef)
            ->set('publish_at', $publishAt);

        $pbjxEvent = new NodeProjectedEvent($node, $event);
        $this->projector->onPollProjected($pbjxEvent);
        $statsRef = $this->createStatsRef($nodeRef);
        $stats = $this->ncr->getNode($statsRef);

        $this->assertTrue(NodeStatus::SCHEDULED()->equals($stats->get('status')));
        $this->assertTrue(NodeStatus::SCHEDULED()->equals($this->ncrSearch->getNode($statsRef)->get('status')));
        $this->assertSame((string)$stats->get('created_at'), (string)Microtime::fromDateTime($publishAt));
    }

    public function testNodePublishedAndUnpublished(): void
    {
        $publishedAt = new \DateTime();
        $node = PollV1::create()
            ->set('title', 'poll-title')
            ->set('status', NodeStatus::PUBLISHED())
            ->set('published_at', $publishedAt);
        $nodeRef = NodeRef::fromNode($node);

        $publishEvent = PollPublishedV1::create()
            ->set('node_ref', $nodeRef)
            ->set('published_at', $publishedAt);
        $publishPbjxEvent = new NodeProjectedEvent($node, $publishEvent);
        $this->projector->onPollProjected($publishPbjxEvent);
        $statsRef = $this->createStatsRef($nodeRef);
        $stats = $this->ncr->getNode($statsRef);

        $this->assertTrue(NodeStatus::PUBLISHED()->equals($stats->get('status')));
        $this->assertTrue(NodeStatus::PUBLISHED()->equals($this->ncrSearch->getNode($statsRef)->get('status')));
        $this->assertSame((string)$stats->get('created_at'), (string)Microtime::fromDateTime($publishedAt));

        $unpublishEvent = PollUnpublishedV1::create()->set('node_ref', $nodeRef);
        $unpublishPbjxEvent = new NodeProjectedEvent((clone $node)->set('status', NodeStatus::DRAFT()), $unpublishEvent);
        $this->projector->onPollProjected($unpublishPbjxEvent);

        $this->assertTrue(NodeStatus::DRAFT()->equals($stats->get('status')));
        $this->assertTrue(NodeStatus::DRAFT()->equals($this->ncrSearch->getNode($statsRef)->get('status')));
    }

    public function testNodeUpdated(): void
    {
        $node = PollV1::create()->set('title', 'poll-title');
        $nodeRef = NodeRef::fromNode($node);
        $newNode = (clone $node)->set('title', 'new-title');
        $event = PollUpdatedV1::create()
            ->set('node_ref', $nodeRef)
            ->set('new_node', $newNode);
        $pbjxEvent = new NodeProjectedEvent($newNode, $event);
        $this->projector->onPollProjected($pbjxEvent);

        $statsRef = $this->createStatsRef($nodeRef);
        $stats = $this->ncr->getNode($statsRef);
        $this->assertSame('new-title', $stats->get('title'));
        $this->assertSame('new-title', $this->ncrSearch->getNode($statsRef)->get('title'));
    }

    public function testNodeCreatedAndUpdated(): void
    {
        $node = PollV1::create()->set('title', 'poll-title');
        $nodeRef = NodeRef::fromNode($node);
        $createdEvent = PollCreatedV1::create()->set('node', $node);
        $createdPbjxEvent = new NodeProjectedEvent($node, $createdEvent);
        $this->projector->onPollProjected($createdPbjxEvent);
        $statsRef = $this->createStatsRef(NodeRef::fromNode($node));
        $stats = $this->ncr->getNode($statsRef);

        $this->assertTrue($node->get('status')->equals($stats->get('status')));
        $this->assertSame((string)$node->get('_id'), (string)$stats->get('_id'));
        $this->assertSame($node->get('title'), $stats->get('title'));
        $this->assertSame((string)$node->get('created_at'), (string)$stats->get('created_at'));

        $this->assertTrue($this->ncrSearch->hasIndexedNode($statsRef));

        $newNode = (clone $node)->set('title', 'new-title');
        $updatedEvent = PollUpdatedV1::create()
            ->set('node_ref', $nodeRef)
            ->set('new_node', $newNode);
        $updatedPbjxEvent = new NodeProjectedEvent($newNode, $updatedEvent);
        $this->projector->onPollProjected($updatedPbjxEvent);

        $statsRef = $this->createStatsRef($nodeRef);
        $stats = $this->ncr->getNode($statsRef);
        $this->assertSame('new-title', $stats->get('title'));
        $this->assertSame('new-title', $this->ncrSearch->getNode($statsRef)->get('title'));
    }

    public function testVoteCasted(): void
    {
        $node = PollV1::create()->set('title', 'poll-title');
        $this->ncr->putNode($node);
        $nodeRef = NodeRef::fromNode($node);
        $answer = PollAnswerV1::create()->set('title', 'answer-title');
        $answerId = $answer->get('_id');
        $event = VoteCastedV1::create()
            ->set('poll_ref', $nodeRef)
            ->set('answer_id', $answerId);
        $this->projector->onVoteCasted($event, $this->pbjx);

        $statsRef = $this->createStatsRef($nodeRef);
        $stats = $this->ncr->getNode($statsRef);

        $this->assertSame(1, $stats->get('votes'));
        $this->assertSame(1, $stats->getFromMap('answer_votes', (string)$answerId));
    }
}
