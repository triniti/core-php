<?php
declare(strict_types=1);

namespace Triniti\Tests\Curator;

use Acme\Schemas\Curator\Event\TeaserPublishedV1;
use Acme\Schemas\Curator\Event\TeaserSlottingRemovedV1;
use Acme\Schemas\Curator\Event\TeaserUpdatedV1;
use Acme\Schemas\Curator\Event\WidgetUpdatedV1;
use Acme\Schemas\Curator\Node\ArticleTeaserV1;
use Acme\Schemas\Curator\Node\BlogrollWidgetV1;
use Acme\Schemas\News\Event\ArticlePublishedV1;
use Acme\Schemas\News\Node\ArticleV1;
use Gdbots\Ncr\Repository\InMemoryNcr;
use Gdbots\Schemas\Ncr\Enum\NodeStatus;
use Psr\Cache\CacheItemPoolInterface;
use Triniti\Curator\NcrTeaserProjector;
use Triniti\Schemas\Curator\Command\RemoveTeaserSlottingV1;
use Triniti\Tests\AbstractPbjxTest;
use Triniti\Tests\MockNcrSearch;
use Triniti\Tests\MockPbjx;

final class NcrTeaserProjectorTest extends AbstractPbjxTest
{
    protected InMemoryNcr $ncr;
    protected NcrTeaserProjector $projector;
    protected MockNcrSearch $ncrSearch;
    protected CacheItemPoolInterface $cache;

    public function setup(): void
    {
        parent::setup();
        $this->ncr = new InMemoryNcr();
        $this->ncrSearch = new MockNcrSearch();
        $this->pbjx = new MockPbjx($this->locator);
        $this->cache = new MockCache();
        $this->projector = new NcrTeaserProjector($this->ncr, $this->ncrSearch, $this->cache);
    }

    public function testOnTeaserPublished(): void
    {
        $node = ArticleV1::create();
        $this->ncr->putNode($node);
        $nodeRef = $node->generateNodeRef();
        $teaser = ArticleTeaserV1::create()
            ->set('target_ref', $nodeRef)
            ->addToMap('slotting', 'home', 1);
        $this->ncr->putNode($teaser);
        $teaserRef = $teaser->generateNodeRef();
        $this->projector->onTeaserPublished(TeaserPublishedV1::create()->set('node_ref', $teaserRef), $this->pbjx);
        $sentCommand = $this->pbjx->getSent()[0];
        $this->assertInstanceOf(RemoveTeaserSlottingV1::class, $sentCommand);
        $this->assertTrue($teaserRef->equals($sentCommand->get('except_ref')));
        $this->assertSame(1, $sentCommand->get('slotting')['home']);
    }

    public function testOnTeaserPublishedIsReplay(): void
    {
        $node = ArticleV1::create();
        $this->ncr->putNode($node);
        $nodeRef = $node->generateNodeRef();
        $teaser = ArticleTeaserV1::create()
            ->set('target_ref', $nodeRef)
            ->addToMap('slotting', 'home', 1);
        $this->ncr->putNode($teaser);
        $teaserRef = $teaser->generateNodeRef();
        $event = TeaserPublishedV1::create()->set('node_ref', $teaserRef);
        $event->isReplay(true);
        $this->projector->onTeaserPublished($event, $this->pbjx);
        $this->assertEmpty($this->pbjx->getSent());
    }

    public function testOnNodePublishedIsNotTeaser(): void
    {
        $node = ArticleV1::create();
        $this->ncr->putNode($node);
        $nodeRef = $node->generateNodeRef();
        $this->projector->onTeaserPublished(ArticlePublishedV1::create()->set('node_ref', $nodeRef), $this->pbjx);
        $this->assertEmpty($this->pbjx->getSent());
    }

    public function testOnTeaserPublishedNoSlotting(): void
    {
        $node = ArticleV1::create();
        $this->ncr->putNode($node);
        $nodeRef = $node->generateNodeRef();
        $teaser = ArticleTeaserV1::create()
            ->set('target_ref', $nodeRef);
        $this->ncr->putNode($teaser);
        $teaserRef = $teaser->generateNodeRef();
        $this->projector->onTeaserPublished(TeaserPublishedV1::create()->set('node_ref', $teaserRef), $this->pbjx);
        $this->assertEmpty($this->pbjx->getSent());
    }

    public function testOnTeaserUpdated(): void
    {
        $node = ArticleV1::create();
        $this->ncr->putNode($node);
        $nodeRef = $node->generateNodeRef();
        $teaser = ArticleTeaserV1::create()
            ->set('target_ref', $nodeRef)
            ->addToMap('slotting', 'home', 1);
        $teaserRef = $teaser->generateNodeRef();
        $this->ncr->putNode($teaser);
        $newNode = (clone $teaser)
            ->set('title', 'new-title')
            ->set('status', NodeStatus::PUBLISHED())
            ->addToMap('slotting', 'home', 2);
        $event = TeaserUpdatedV1::create()
            ->set('node_ref', $teaserRef)
            ->set('old_node', $teaser)
            ->set('new_node', $newNode);
        $this->projector->onTeaserUpdated($event, $this->pbjx);
        $sentCommand = $this->pbjx->getSent()[0];
        $this->assertInstanceOf(RemoveTeaserSlottingV1::class, $sentCommand);
        $this->assertTrue($teaserRef->equals($sentCommand->get('except_ref')));
        $this->assertSame(2, $sentCommand->get('slotting')['home']);
    }

    public function testOnTeaserUpdatedNoNewSlotting(): void
    {
        $node = ArticleV1::create();
        $this->ncr->putNode($node);
        $nodeRef = $node->generateNodeRef();
        $teaser = ArticleTeaserV1::create()
            ->set('target_ref', $nodeRef)
            ->addToMap('slotting', 'home', 1);
        $teaserRef = $teaser->generateNodeRef();
        $this->ncr->putNode($teaser);
        $newNode = (clone $teaser)
            ->set('title', 'new-title')
            ->set('status', NodeStatus::PUBLISHED());
        $event = TeaserUpdatedV1::create()
            ->set('node_ref', $teaserRef)
            ->set('old_node', $teaser)
            ->set('new_node', $newNode);
        $this->projector->onTeaserUpdated($event, $this->pbjx);
        $this->assertEmpty($this->pbjx->getSent());
    }

    public function testOnTeaserUpdatedSameSlotting(): void
    {
        $node = ArticleV1::create();
        $this->ncr->putNode($node);
        $nodeRef = $node->generateNodeRef();
        $teaser = ArticleTeaserV1::create()
            ->set('target_ref', $nodeRef)
            ->addToMap('slotting', 'home', 1);
        $teaserRef = $teaser->generateNodeRef();
        $this->ncr->putNode($teaser);
        $newNode = (clone $teaser)
            ->set('title', 'new-title')
            ->set('status', NodeStatus::PUBLISHED())
            ->addToMap('slotting', 'home', 1);
        $event = TeaserUpdatedV1::create()
            ->set('node_ref', $teaserRef)
            ->set('old_node', $teaser)
            ->set('new_node', $newNode);
        $this->projector->onTeaserUpdated($event, $this->pbjx);
        $this->assertEmpty($this->pbjx->getSent());
    }

    public function testOnTeaserUpdatedNotPublished(): void
    {
        $node = ArticleV1::create();
        $this->ncr->putNode($node);
        $nodeRef = $node->generateNodeRef();
        $teaser = ArticleTeaserV1::create()
            ->set('target_ref', $nodeRef)
            ->addToMap('slotting', 'home', 1);
        $teaserRef = $teaser->generateNodeRef();
        $this->ncr->putNode($teaser);
        $newNode = (clone $teaser)
            ->set('title', 'new-title')
            ->addToMap('slotting', 'home', 2);
        $event = TeaserUpdatedV1::create()
            ->set('node_ref', $teaserRef)
            ->set('old_node', $teaser)
            ->set('new_node', $newNode);
        $this->projector->onTeaserUpdated($event, $this->pbjx);
        $this->assertEmpty($this->pbjx->getSent());
    }

    public function testOnNodeUpdatedNotTeaser(): void
    {
        $node = BlogrollWidgetV1::create();
        $this->ncr->putNode($node);
        $event = WidgetUpdatedV1::create()
            ->set('node_ref', $node->generateNodeRef())
            ->set('old_node', $node)
            ->set('new_node', (clone $node)->set('title', 'new-title'));
        $this->projector->onTeaserUpdated($event, $this->pbjx);
        $this->assertEmpty($this->pbjx->getSent());
    }

    public function testOnTeaserUpdatedIsReplay(): void
    {
        $node = ArticleV1::create();
        $this->ncr->putNode($node);
        $nodeRef = $node->generateNodeRef();
        $teaser = ArticleTeaserV1::create()
            ->set('target_ref', $nodeRef)
            ->addToMap('slotting', 'home', 1);
        $teaserRef = $teaser->generateNodeRef();
        $this->ncr->putNode($teaser);
        $newNode = (clone $teaser)
            ->set('title', 'new-title')
            ->set('status', NodeStatus::PUBLISHED())
            ->addToMap('slotting', 'home', 2);
        $event = TeaserUpdatedV1::create()
            ->set('node_ref', $teaserRef)
            ->set('old_node', $teaser)
            ->set('new_node', $newNode);
        $event->isReplay(true);
        $this->projector->onTeaserUpdated($event, $this->pbjx);
        $this->assertEmpty($this->pbjx->getSent());
    }

    public function testOnTeaserUpdatedNoOldNode(): void
    {
        $node = ArticleV1::create();
        $this->ncr->putNode($node);
        $nodeRef = $node->generateNodeRef();
        $teaser = ArticleTeaserV1::create()
            ->set('target_ref', $nodeRef)
            ->addToMap('slotting', 'home', 1);
        $teaserRef = $teaser->generateNodeRef();
        $this->ncr->putNode($teaser);
        $newNode = (clone $teaser)
            ->set('title', 'new-title')
            ->set('status', NodeStatus::PUBLISHED())
            ->addToMap('slotting', 'home', 2);
        $event = TeaserUpdatedV1::create()
            ->set('node_ref', $teaserRef)
            ->set('new_node', $newNode);
        $this->projector->onTeaserUpdated($event, $this->pbjx);
        $this->assertEmpty($this->pbjx->getSent());
    }

    public function testOnTeaserSlottingRemoved(): void
    {
        $node = ArticleTeaserV1::create()
            ->set('target_ref', ArticleV1::create()->generateNodeRef())
            ->addToMap('slotting', 'home', 1);
        $nodeRef = $node->generateNodeRef();
        $this->ncr->putNode($node);
        $event = TeaserSlottingRemovedV1::create()
            ->set('node_ref', $node->generateNodeRef())
            ->addToSet('slotting_keys', ['home']);
        $this->projector->onTeaserSlottingRemoved($event, $this->pbjx);
        $this->assertTrue(null === $this->ncr->getNode($nodeRef)->getFromMap('slotting' , 'home'));
    }
}
