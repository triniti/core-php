<?php
declare(strict_types=1);

namespace Triniti\Tests\Curator;

use Acme\Schemas\Curator\Command\SyncTeaserV1;
use Acme\Schemas\Curator\Node\ArticleTeaserV1;
use Acme\Schemas\News\Node\ArticleV1;
use Gdbots\Ncr\Repository\InMemoryNcr;
use Gdbots\Pbj\WellKnown\NodeRef;
use Gdbots\Schemas\Ncr\Enum\NodeStatus;
use Gdbots\Schemas\Ncr\Event\NodeCreatedV1;
use Gdbots\Schemas\Ncr\Event\NodeUpdatedV1;
use Gdbots\Schemas\Pbjx\StreamId;
use Triniti\Curator\SyncTeaserHandler;
use Triniti\Curator\TeaserTransformer;
use Triniti\Sys\Flags;
use Triniti\Tests\AbstractPbjxTest;
use Triniti\Tests\MockNcr;

final class SyncTeaserHandlerTest extends AbstractPbjxTest
{
    public function testHandleSyncByTargetRefWithoutTeasers(): void
    {
        $ncr = new InMemoryNcr();
        $article = ArticleV1::create()
            ->set('title', 'article title');
        $ncr->putNode($article);

        $command = SyncTeaserV1::create()->set('target_ref', NodeRef::fromNode($article));
        $syncTeaserHandler = new SyncTeaserHandler($ncr, new Flags($ncr, 'acme:flagset:test'), new TeaserTransformer());
        $syncTeaserHandler->handleCommand($command, $this->pbjx);

        foreach ($this->pbjx->getEventStore()->pipeAllEvents() as [$event, $streamId]) {
            $this->assertTrue($event instanceof NodeCreatedV1);
            $this->assertTrue($event->get('node')->get('target_ref')->equals($article->generateNodeRef()));
        }
    }

    public function testHandleSyncByTargetRefWithTeasers(): void
    {
        $ncr = new MockNcr();
        $article = ArticleV1::create()
            ->set('title', 'article title')
            ->set('status', NodeStatus::PUBLISHED());
        $teaser1 = ArticleTeaserV1::create()
            ->set('target_ref', NodeRef::fromNode($article))
            ->set('sync_with_target', true)
            ->set('title', 'teaser title 1');
        $teaser2 = ArticleTeaserV1::create()
            ->set('target_ref', NodeRef::fromNode($article))
            ->set('sync_with_target', true)
            ->set('title', 'teaser title 2');

        $ncr->putNode($article);
        $ncr->putNode($teaser1);
        $ncr->putNode($teaser2);

        $command = SyncTeaserV1::create()->set('target_ref', NodeRef::fromNode($article));
        $syncTeaserHandler = new SyncTeaserHandler($ncr, new Flags($ncr, 'acme:flagset:test'), new TeaserTransformer());
        $syncTeaserHandler->handleCommand($command, $this->pbjx);

        foreach ([$teaser1, $teaser2] as $teaser) {
            foreach ($this->pbjx->getEventStore()->pipeEvents(StreamId::fromNodeRef($teaser->generateNodeRef())) as $event) {
                $this->assertTrue($event instanceof NodeUpdatedV1);
                $this->assertTrue($event->get('node_ref')->equals($teaser->generateNodeRef()));
                $this->assertSame('article title', $event->get('new_node')->get('title'));
            }
        }
    }

    public function testHandleSyncByTargetRefWithTeasersMixedSync(): void
    {
        $ncr = new MockNcr();
        $article = ArticleV1::create()
            ->set('title', 'article title')
            ->set('status', NodeStatus::create('published'));
        $teaser1 = ArticleTeaserV1::create()
            ->set('target_ref', NodeRef::fromNode($article))
            ->set('sync_with_target', true)
            ->set('title', 'teaser title 1');
        $teaser2 = ArticleTeaserV1::create()
            ->set('target_ref', NodeRef::fromNode($article))
            ->set('sync_with_target', false)
            ->set('title', 'teaser title 2');
        $ncr->putNode($article);
        $ncr->putNode($teaser1);
        $ncr->putNode($teaser2);

        $command = SyncTeaserV1::create()->set('target_ref', NodeRef::fromNode($article));
        $syncTeaserHandler = new SyncTeaserHandler($ncr, new Flags($ncr, 'acme:flagset:test'), new TeaserTransformer());
        $syncTeaserHandler->handleCommand($command, $this->pbjx);

        foreach ($this->pbjx->getEventStore()->pipeEvents(StreamId::fromNodeRef($teaser1->generateNodeRef())) as $event) {
            $this->assertTrue($event instanceof NodeUpdatedV1);
            $this->assertTrue($event->get('node_ref')->equals($teaser1->generateNodeRef()));
            $this->assertSame('article title', $event->get('new_node')->get('title'));
        }

        $teaser2EventCount = 0;
        foreach ($this->pbjx->getEventStore()->pipeEvents(StreamId::fromNodeRef($teaser2->generateNodeRef())) as $event) {
            $teaser2EventCount++;
        }
        $this->assertSame(0, $teaser2EventCount);
    }

    public function testHandleSyncByTeaserRef(): void
    {
        $ncr = new MockNcr();
        $article = ArticleV1::create()
            ->set('title', 'article title')
            ->set('status', NodeStatus::create('deleted'));

        $teaser = ArticleTeaserV1::create()
            ->set('target_ref', NodeRef::fromNode($article))
            ->set('sync_with_target', false)
            ->set('title', 'teaser title 1');

        $ncr->putNode($article);
        $ncr->putNode($teaser);

        $command = SyncTeaserV1::create()->set('teaser_ref', NodeRef::fromNode($teaser));
        $syncTeaserHandler = new SyncTeaserHandler($ncr, new Flags($ncr, 'acme:flagset:test'), new TeaserTransformer());
        $syncTeaserHandler->handleCommand($command, $this->pbjx);

        foreach ($this->pbjx->getEventStore()->pipeEvents(StreamId::fromNodeRef($teaser->generateNodeRef())) as $event) {
            $this->assertTrue($event instanceof NodeUpdatedV1);
            $this->assertTrue($event->get('node_ref')->equals($teaser->generateNodeRef()));
            $this->assertSame('article title', $event->get('new_node')->get('title'));
        }
    }

    public function testDontSyncIfEtagIdentical(): void
    {
        $ncr = new MockNcr();
        $article = ArticleV1::create()
            ->set('title', 'article title');

        $teaser = ArticleTeaserV1::create()
            ->set('target_ref', NodeRef::fromNode($article))
            ->set('title', 'article title');

        $ncr->putNode($article);
        $ncr->putNode($teaser);

        $command = SyncTeaserV1::create()->set('teaser_ref', NodeRef::fromNode($teaser));
        $syncTeaserHandler = new SyncTeaserHandler($ncr, new Flags($ncr, 'acme:flagset:test'), new TeaserTransformer());
        $syncTeaserHandler->handleCommand($command, $this->pbjx);

        $eventCount = 0;
        foreach ($this->pbjx->getEventStore()->pipeEvents(StreamId::fromNodeRef($teaser->generateNodeRef())) as $event) {
            $eventCount++;
        }
        $this->assertSame(0, $eventCount);
    }
}
