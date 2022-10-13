<?php
declare(strict_types=1);

namespace Triniti\Tests\Apollo;

use Acme\Schemas\Apollo\Node\ReactionsV1;
use Acme\Schemas\News\Event\ArticleCreatedV1;
use Acme\Schemas\News\Event\ArticleDeletedV1;
use Acme\Schemas\News\Event\ArticleExpiredV1;
use Acme\Schemas\News\Event\ArticleMarkedAsDraftV1;
use Acme\Schemas\News\Event\ArticleMarkedAsPendingV1;
use Acme\Schemas\News\Event\ArticlePublishedV1;
use Acme\Schemas\News\Event\ArticleScheduledV1;
use Acme\Schemas\News\Event\ArticleUnpublishedV1;
use Acme\Schemas\News\Event\ArticleUpdatedV1;
use Acme\Schemas\News\Node\ArticleV1;
use Aws\DynamoDb\DynamoDbClient;
use Gdbots\Ncr\Event\NodeProjectedEvent;
use Gdbots\Ncr\Exception\NodeNotFound;
use Gdbots\Ncr\Repository\DynamoDb\TableManager;
use Gdbots\Ncr\Repository\InMemoryNcr;
use Gdbots\Pbj\WellKnown\Microtime;
use Gdbots\Pbj\WellKnown\NodeRef;
use Gdbots\Schemas\Ncr\Enum\NodeStatus;
use Triniti\Apollo\NcrReactionsProjector;
use Triniti\Schemas\Apollo\Event\ReactionsAddedV1;
use Triniti\Tests\AbstractPbjxTest;

final class NcrReactionsProjectorTest extends AbstractPbjxTest
{
    private NcrReactionsProjector $projector;
    private InMemoryNcr $ncr;
    private TableManager $tableManager;
    private DynamoDbClient $dynamoDbClient;

    public function setup(): void
    {
        parent::setup();
        $this->ncr = new InMemoryNcr();
        $this->tableManager = new TableManager('test');
        $this->dynamoDbClient = new DynamoDbClient(['region' => 'us-west-2', 'version' => '2012-08-10']);
        $this->projector = new NcrReactionsProjector($this->dynamoDbClient, $this->tableManager, $this->ncr);
    }

    protected function createReactionsRef(NodeRef $nodeRef): NodeRef
    {
        return NodeRef::fromString(str_replace($nodeRef->getLabel() . ':', 'reactions:', $nodeRef->toString()));
    }

    public function testNodeCreated(): void
    {
        $node = ArticleV1::create()->set('title', 'article-title');
        $event = ArticleCreatedV1::create()->set('node', $node);
        $pbjxEvent = new NodeProjectedEvent($node, $event);
        $this->projector->onNodeProjected($pbjxEvent);
        $reactionsRef = $this->createReactionsRef(NodeRef::fromNode($node));
        $reactions = $this->ncr->getNode($reactionsRef);

        $this->assertTrue($node->get('status') === $reactions->get('status'));
        $this->assertSame((string)$node->get('_id'), (string)$reactions->get('_id'));
        $this->assertSame($node->get('title'), $reactions->get('title'));
        $this->assertSame((string)$node->get('created_at'), (string)$reactions->get('created_at'));
        $this->assertSame('article', $reactions->get('target'));
    }

    public function testNodeCreatedAndDeleted(): void
    {
        $node = ArticleV1::create()->set('title', 'article-title');
        $nodeRef = NodeRef::fromNode($node);
        $createdEvent = ArticleCreatedV1::create()->set('node', $node);
        $createdPbjxEvent = new NodeProjectedEvent($node, $createdEvent);
        $this->projector->onNodeProjected($createdPbjxEvent);
        $reactionsRef = $this->createReactionsRef(NodeRef::fromNode($node));
        $reactions = $this->ncr->getNode($reactionsRef);

        $this->assertTrue($node->get('status') === $reactions->get('status'));
        $this->assertSame((string)$node->get('_id'), (string)$reactions->get('_id'));
        $this->assertSame($node->get('title'), $reactions->get('title'));
        $this->assertSame((string)$node->get('created_at'), (string)$reactions->get('created_at'));
        $this->assertSame('article', $reactions->get('target'));


        $deletedEvent = ArticleDeletedV1::create()->set('node_ref', $nodeRef);
        $deletedPbjxEvent = new NodeProjectedEvent((clone $node)->set('status', NodeStatus::DELETED), $deletedEvent);
        $this->projector->onNodeProjected($deletedPbjxEvent);

        $this->expectException(NodeNotFound::class);
        $this->ncr->getNode($reactionsRef);
    }

    public function testNodeExpired(): void
    {
        $node = ArticleV1::create()
            ->set('title', 'article-title')
            ->set('status', NodeStatus::EXPIRED);
        $nodeRef = NodeRef::fromNode($node);
        $event = ArticleExpiredV1::create()->set('node_ref', $nodeRef);
        $createdPbjxEvent = new NodeProjectedEvent($node, $event);
        $this->projector->onNodeProjected($createdPbjxEvent);
        $reactionsRef = $this->createReactionsRef($nodeRef);
        $reactions = $this->ncr->getNode($reactionsRef);

        $this->assertTrue(NodeStatus::EXPIRED === $reactions->get('status'));
    }

    public function testNodeMarkedAsDraft(): void
    {
        $node = ArticleV1::create()->set('title', 'article-title');
        $nodeRef = NodeRef::fromNode($node);
        $event = ArticleMarkedAsDraftV1::create()->set('node_ref', $nodeRef);
        $createdPbjxEvent = new NodeProjectedEvent($node, $event);
        $this->projector->onNodeProjected($createdPbjxEvent);
        $reactionsRef = $this->createReactionsRef($nodeRef);
        $reactions = $this->ncr->getNode($reactionsRef);

        $this->assertTrue(NodeStatus::DRAFT === $reactions->get('status'));
    }

    public function testNodeMarkedAsPending(): void
    {
        $node = ArticleV1::create()
            ->set('title', 'article-title')
            ->set('status', NodeStatus::PENDING);
        $nodeRef = NodeRef::fromNode($node);
        $event = ArticleMarkedAsPendingV1::create()->set('node_ref', $nodeRef);
        $createdPbjxEvent = new NodeProjectedEvent($node, $event);
        $this->projector->onNodeProjected($createdPbjxEvent);
        $reactionsRef = $this->createReactionsRef($nodeRef);
        $reactions = $this->ncr->getNode($reactionsRef);

        $this->assertTrue(NodeStatus::PENDING === $reactions->get('status'));
    }

    public function testNodePublished(): void
    {
        $publishedAt = new \DateTime();
        $node = ArticleV1::create()
            ->set('title', 'article-title')
            ->set('status', NodeStatus::PUBLISHED)
            ->set('published_at', $publishedAt);
        $nodeRef = NodeRef::fromNode($node);
        $event = ArticlePublishedV1::create()
            ->set('node_ref', $nodeRef)
            ->set('published_at', $publishedAt);
        $createdPbjxEvent = new NodeProjectedEvent($node, $event);
        $this->projector->onNodeProjected($createdPbjxEvent);
        $reactionsRef = $this->createReactionsRef($nodeRef);
        $reactions = $this->ncr->getNode($reactionsRef);

        $this->assertTrue(NodeStatus::PUBLISHED === $reactions->get('status'));
        $this->assertSame((string)$reactions->get('created_at'), (string)Microtime::fromDateTime($publishedAt));
    }

    public function testNodeScheduled(): void
    {
        $publishAt = new \DateTime();
        $node = ArticleV1::create()
            ->set('title', 'article-title')
            ->set('status', NodeStatus::SCHEDULED)
            ->set('published_at', $publishAt);
        $nodeRef = NodeRef::fromNode($node);
        $event = ArticleScheduledV1::create()
            ->set('node_ref', $nodeRef)
            ->set('publish_at', $publishAt);

        $pbjxEvent = new NodeProjectedEvent($node, $event);
        $this->projector->onNodeProjected($pbjxEvent);
        $reactionsRef = $this->createReactionsRef($nodeRef);
        $reactions = $this->ncr->getNode($reactionsRef);

        $this->assertTrue(NodeStatus::SCHEDULED === $reactions->get('status'));
        $this->assertSame((string)$reactions->get('created_at'), (string)Microtime::fromDateTime($publishAt));
    }

    public function testNodePublishedAndUnpublished(): void
    {
        $publishedAt = new \DateTime();
        $node = ArticleV1::create()
            ->set('title', 'article-title')
            ->set('status', NodeStatus::PUBLISHED)
            ->set('published_at', $publishedAt);
        $nodeRef = NodeRef::fromNode($node);

        $publishEvent = ArticlePublishedV1::create()
            ->set('node_ref', $nodeRef)
            ->set('published_at', $publishedAt);
        $publishPbjxEvent = new NodeProjectedEvent($node, $publishEvent);
        $this->projector->onNodeProjected($publishPbjxEvent);
        $reactionsRef = $this->createReactionsRef($nodeRef);
        $reactions = $this->ncr->getNode($reactionsRef);

        $this->assertTrue(NodeStatus::PUBLISHED === $reactions->get('status'));
        $this->assertSame((string)$reactions->get('created_at'), (string)Microtime::fromDateTime($publishedAt));

        $unpublishEvent = ArticleUnpublishedV1::create()->set('node_ref', $nodeRef);
        $unpublishPbjxEvent = new NodeProjectedEvent((clone $node)->set('status', NodeStatus::DRAFT), $unpublishEvent);
        $this->projector->onNodeProjected($unpublishPbjxEvent);

        $this->assertTrue(NodeStatus::DRAFT === $reactions->get('status'));
    }

    public function testNodeUpdated(): void
    {
        $node = ArticleV1::create()->set('title', 'article-title');
        $nodeRef = NodeRef::fromNode($node);
        $newNode = (clone $node)->set('title', 'new-title');
        $event = ArticleUpdatedV1::create()
            ->set('node_ref', $nodeRef)
            ->set('new_node', $newNode);
        $pbjxEvent = new NodeProjectedEvent($newNode, $event);
        $this->projector->onNodeProjected($pbjxEvent);

        $reactionsRef = $this->createReactionsRef($nodeRef);
        $reactions = $this->ncr->getNode($reactionsRef);
        $this->assertSame('new-title', $reactions->get('title'));
    }

    public function testNodeCreatedAndUpdated(): void
    {
        $node = ArticleV1::create()->set('title', 'article-title');
        $nodeRef = NodeRef::fromNode($node);
        $createdEvent = ArticleCreatedV1::create()->set('node', $node);
        $createdPbjxEvent = new NodeProjectedEvent($node, $createdEvent);
        $this->projector->onNodeProjected($createdPbjxEvent);
        $reactionsRef = $this->createReactionsRef(NodeRef::fromNode($node));
        $reactions = $this->ncr->getNode($reactionsRef);

        $this->assertTrue($node->get('status') === $reactions->get('status'));
        $this->assertSame((string)$node->get('_id'), (string)$reactions->get('_id'));
        $this->assertSame($node->get('title'), $reactions->get('title'));
        $this->assertSame((string)$node->get('created_at'), (string)$reactions->get('created_at'));
        $this->assertSame('article', $reactions->get('target'));

        $newNode = (clone $node)->set('title', 'new-title');
        $updatedEvent = ArticleUpdatedV1::create()
            ->set('node_ref', $nodeRef)
            ->set('new_node', $newNode);
        $updatedPbjxEvent = new NodeProjectedEvent($newNode, $updatedEvent);
        $this->projector->onNodeProjected($updatedPbjxEvent);

        $reactionsRef = $this->createReactionsRef($nodeRef);
        $reactions = $this->ncr->getNode($reactionsRef);
        $this->assertSame('new-title', $reactions->get('title'));
    }
}
