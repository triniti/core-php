<?php
declare(strict_types=1);

namespace Triniti\Tests\Apollo;

use Acme\Schemas\Apollo\Node\ReactionsV1;
use Acme\Schemas\News\Event\ArticleCreatedV1;
use Acme\Schemas\News\Event\ArticleDeletedV1;
use Acme\Schemas\News\Node\ArticleV1;
use Aws\DynamoDb\DynamoDbClient;
use Gdbots\Ncr\Event\NodeProjectedEvent;
use Gdbots\Ncr\Exception\NodeNotFound;
use Gdbots\Ncr\Repository\DynamoDb\TableManager;
use Gdbots\Ncr\Repository\InMemoryNcr;
use Gdbots\Pbj\WellKnown\NodeRef;
use Gdbots\Schemas\Ncr\Enum\NodeStatus;
use Triniti\Apollo\NcrReactionsProjector;
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
        $this->projector->onNodeCreated($pbjxEvent);
        $reactionsRef = $this->createReactionsRef(NodeRef::fromNode($node));
        $reactions = $this->ncr->getNode($reactionsRef);

        $this->assertTrue(NodeStatus::PUBLISHED === $reactions->get('status'));
        $this->assertSame((string)$node->get('_id'), (string)$reactions->get('_id'));
        $this->assertSame($node->get('title'), $reactions->get('title'));
        $this->assertSame((string)$node->get('created_at'), (string)$reactions->get('created_at'));
        $this->assertSame('article', $reactions->get('target'));
    }

    public function testNodeDeleted(): void
    {
        $this->expectException(NodeNotFound::class);
        $node = ArticleV1::create()->set('title', 'article-title');
        $reactions = ReactionsV1::fromArray(['_id' => NodeRef::fromNode($node)->getId()]);
        $reactionsRef = $this->createReactionsRef(NodeRef::fromNode($node));
        $this->ncr->putNode($reactions);

        $deletedEvent = ArticleDeletedV1::create()->set('node_ref', NodeRef::fromNode($node));
        $deletedPbjxEvent = new NodeProjectedEvent($node, $deletedEvent);
        $this->projector->onNodeDeleted($deletedPbjxEvent);
        $this->ncr->getNode($reactionsRef);
    }
}
