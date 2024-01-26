<?php
declare(strict_types=1);

namespace Triniti\Tests\Curator;

use Acme\Schemas\Curator\Node\ArticleTeaserV1;
use Acme\Schemas\News\Event\ArticleCreatedV1;
use Acme\Schemas\News\Event\ArticleDeletedV1;
use Acme\Schemas\News\Event\ArticleExpiredV1;
use Acme\Schemas\News\Event\ArticlePublishedV1;
use Acme\Schemas\News\Event\ArticleScheduledV1;
use Acme\Schemas\News\Event\ArticleUnpublishedV1;
use Acme\Schemas\News\Event\ArticleUpdatedV1;
use Acme\Schemas\News\Node\ArticleV1;
use Gdbots\Ncr\Event\NodeProjectedEvent;
use Gdbots\Schemas\Ncr\Command\DeleteNodeV1;
use Gdbots\Schemas\Ncr\Command\ExpireNodeV1;
use Gdbots\Schemas\Ncr\Command\PublishNodeV1;
use Gdbots\Schemas\Ncr\Command\UnpublishNodeV1;
use Gdbots\Schemas\Ncr\Enum\NodeStatus;
use Triniti\Curator\TeaserableWatcher;
use Triniti\Schemas\Curator\Command\SyncTeaserV1;
use Triniti\Tests\AbstractPbjxTest;
use Triniti\Tests\MockNcr;
use Triniti\Tests\MockPbjx;

final class TeaserableWatcherTest extends AbstractPbjxTest
{
    protected MockNcr $ncr;

    public function setup(): void
    {
        parent::setup();
        $this->pbjx = new MockPbjx($this->locator);
        $this->ncr = new MockNcr();
    }

    public function testOnNodeCreated(): void
    {
        $node = ArticleV1::create();
        $watcher = new TeaserableWatcher($this->ncr);

        $pbjxEvent = new NodeProjectedEvent($node, ArticleCreatedV1::create()->set('node', $node));
        $watcher->syncTeasers($pbjxEvent);
        $sentCommand = $this->pbjx->getSent()[0]['command'];
        $this->assertInstanceOf(SyncTeaserV1::class, $sentCommand);
        $this->assertTrue($node->generateNodeRef()->equals($sentCommand->get('target_ref')));
    }

    public function testOnNodeCreatedIsReplay(): void
    {
        $node = ArticleV1::create();
        $watcher = new TeaserableWatcher($this->ncr);
        $event = ArticleCreatedV1::create()->set('node', $node);
        $event->isReplay(true);

        $pbjxEvent = new NodeProjectedEvent($node, $event);
        $watcher->syncTeasers($pbjxEvent);

        $this->assertEmpty($this->pbjx->getSent());
    }

    public function testOnNodeDeleted(): void
    {
        $node = ArticleV1::create();
        $nodeRef = $node->generateNodeRef();
        $teaser = ArticleTeaserV1::create()
            ->set('target_ref', $nodeRef)
            ->set('status', NodeStatus::PUBLISHED);
        $this->ncr->putNode($teaser);
        $watcher = new TeaserableWatcher($this->ncr);

        $pbjxEvent = new NodeProjectedEvent($node, ArticleDeletedV1::create()->set('node_ref', $nodeRef));
        $watcher->deactivateTeasers($pbjxEvent);
        $sentCommand = $this->pbjx->getSent()[0]['command'];
        $this->assertInstanceOf(DeleteNodeV1::class, $sentCommand);
        $this->assertTrue($teaser->generateNodeRef()->equals($sentCommand->get('node_ref')));
    }

    public function testOnNodeDeletedIsReplay(): void
    {
        $node = ArticleV1::create();
        $nodeRef = $node->generateNodeRef();
        $teaser = ArticleTeaserV1::create()
            ->set('target_ref', $nodeRef)
            ->set('status', NodeStatus::PUBLISHED);
        $this->ncr->putNode($teaser);
        $watcher = new TeaserableWatcher($this->ncr);
        $event = ArticleDeletedV1::create()->set('node_ref', $nodeRef);
        $event->isReplay(true);

        $pbjxEvent = new NodeProjectedEvent($node, $event);
        $watcher->deactivateTeasers($pbjxEvent);
        $this->assertEmpty($this->pbjx->getSent());
    }

    public function testOnNodeExpired(): void
    {
        $node = ArticleV1::create();
        $nodeRef = $node->generateNodeRef();
        $teaser = ArticleTeaserV1::create()
            ->set('target_ref', $nodeRef)
            ->set('status', NodeStatus::PUBLISHED);
        $this->ncr->putNode($teaser);
        $watcher = new TeaserableWatcher($this->ncr);

        $pbjxEvent = new NodeProjectedEvent($node, ArticleExpiredV1::create()->set('node_ref', $nodeRef));

        $watcher->deactivateTeasers($pbjxEvent);
        $sentCommand = $this->pbjx->getSent()[0]['command'];
        $this->assertInstanceOf(ExpireNodeV1::class, $sentCommand);
        $this->assertTrue($teaser->generateNodeRef()->equals($sentCommand->get('node_ref')));
    }

    public function testOnNodePublished(): void
    {
        $node = ArticleV1::create();
        $nodeRef = $node->generateNodeRef();
        $teaser = ArticleTeaserV1::create()
            ->set('target_ref', $nodeRef)
            ->set('sync_with_target', true);
        $this->ncr->putNode($teaser);
        $watcher = new TeaserableWatcher($this->ncr);
        $publishedAt = new \DateTime('2099-01-01');
        $event = ArticlePublishedV1::create()
            ->set('node_ref', $nodeRef)
            ->set('published_at', $publishedAt);

        $pbjxEvent = new NodeProjectedEvent($node, $event);
        $watcher->activateTeasers($pbjxEvent);
        $sentCommand = $this->pbjx->getSent()[0]['command'];
        $this->assertInstanceOf(PublishNodeV1::class, $sentCommand);
        $this->assertTrue($teaser->generateNodeRef()->equals($sentCommand->get('node_ref')));
        $this->assertSame($publishedAt->format('Y-m-d'), $sentCommand->get('publish_at')->format('Y-m-d'));
    }

    public function testOnNodePublishedIsReplay(): void
    {
        $node = ArticleV1::create();
        $nodeRef = $node->generateNodeRef();
        $teaser = ArticleTeaserV1::create()
            ->set('target_ref', $nodeRef)
            ->set('sync_with_target', true);
        $this->ncr->putNode($teaser);
        $watcher = new TeaserableWatcher($this->ncr);
        $publishedAt = new \DateTime('2099-01-01');
        $event = ArticlePublishedV1::create()
            ->set('node_ref', $nodeRef)
            ->set('published_at', $publishedAt);
        $event->isReplay(true);

        $pbjxEvent = new NodeProjectedEvent($node, $event);
        $watcher->activateTeasers($pbjxEvent);
        $this->assertEmpty($this->pbjx->getSent());
    }

    public function testOnNodeScheduled(): void
    {
        $node = ArticleV1::create();
        $nodeRef = $node->generateNodeRef();
        $teaser = ArticleTeaserV1::create()
            ->set('target_ref', $nodeRef)
            ->set('sync_with_target', true);
        $this->ncr->putNode($teaser);
        $watcher = new TeaserableWatcher($this->ncr);
        $publishAt = new \DateTime('2099-01-01');
        $event = ArticleScheduledV1::create()
            ->set('node_ref', $nodeRef)
            ->set('publish_at', $publishAt);

        $pbjxEvent = new NodeProjectedEvent($node, $event);
        $watcher->activateTeasers($pbjxEvent);
        $sentCommand = $this->pbjx->getSent()[0]['command'];
        $this->assertInstanceOf(PublishNodeV1::class, $sentCommand);
        $this->assertTrue($teaser->generateNodeRef()->equals($sentCommand->get('node_ref')));
        $this->assertSame($publishAt->format('Y-m-d'), $sentCommand->get('publish_at')->format('Y-m-d'));
    }

    public function testOnNodeUnpublished(): void
    {
        $node = ArticleV1::create();
        $nodeRef = $node->generateNodeRef();
        $teaser = ArticleTeaserV1::create()
            ->set('target_ref', $nodeRef)
            ->set('sync_with_target', true)
            ->set('status', NodeStatus::PUBLISHED);
        $this->ncr->putNode($teaser);
        $watcher = new TeaserableWatcher($this->ncr);
        $event = ArticleUnpublishedV1::create()
            ->set('node_ref', $nodeRef);

        $pbjxEvent = new NodeProjectedEvent($node, $event);
        $watcher->deactivateTeasers($pbjxEvent);
        $sentCommand = $this->pbjx->getSent()[0]['command'];
        $this->assertInstanceOf(UnpublishNodeV1::class, $sentCommand);
        $this->assertTrue($teaser->generateNodeRef()->equals($sentCommand->get('node_ref')));
    }

    public function testOnNodeUpdated(): void
    {
        $node = ArticleV1::create();
        $nodeRef = $node->generateNodeRef();
        $watcher = new TeaserableWatcher($this->ncr);
        $event = ArticleUpdatedV1::create()
            ->set('node_ref', $nodeRef)
            ->set('new_node', (clone $node)->set('title', 'new-title'));

        $pbjxEvent = new NodeProjectedEvent($node, $event);
        $watcher->syncTeasers($pbjxEvent);
        $sentCommand = $this->pbjx->getSent()[0]['command'];
        $this->assertInstanceOf(SyncTeaserV1::class, $sentCommand);
        $this->assertTrue($nodeRef->equals($sentCommand->get('target_ref')));
    }
}
