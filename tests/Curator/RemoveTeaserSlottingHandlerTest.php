<?php
declare(strict_types=1);

namespace Triniti\Tests\Curator;

use Acme\Schemas\Curator\Node\ArticleTeaserV1;
use Acme\Schemas\News\Node\ArticleV1;
use Gdbots\Ncr\AggregateResolver;
use Gdbots\Ncr\Repository\InMemoryNcr;
use Gdbots\Pbj\SchemaCurie;
use Gdbots\Schemas\Pbjx\StreamId;
use Triniti\Curator\RemoveTeaserSlottingHandler;
use Triniti\Schemas\Curator\Command\RemoveTeaserSlottingV1;
use Triniti\Schemas\Curator\Event\TeaserSlottingRemovedV1;
use Triniti\Tests\AbstractPbjxTest;
use Triniti\Tests\MockNcrSearch;

final class RemoveTeaserSlottingHandlerTest extends AbstractPbjxTest
{
    protected InMemoryNcr $ncr;

    public function setup(): void
    {
        parent::setup();
        $this->ncr = new InMemoryNcr();
    }

    public function testHandleCommand(): void
    {
        $teaser = ArticleTeaserV1::create()
            ->set('target_ref', ArticleV1::create()->generateNodeRef())
            ->addToMap('slotting', 'home', 1);
        $nodeRef = $teaser->generateNodeRef();
        $ncrSearch = new MockNcrSearch();
        $ncrSearch->indexNodes([$teaser]);
        $this->locator->registerRequestHandler(
            SchemaCurie::fromString('triniti:curator:request:search-teasers-request'),
            new MockSearchNodesRequestHandler($ncrSearch)
        );
        AggregateResolver::register(['acme:article-teaser' => 'Triniti\Curator\TeaserAggregate']);
        $command = RemoveTeaserSlottingV1::create()->addToMap('slotting', 'home', 1);
        $handler = new RemoveTeaserSlottingHandler();
        $handler->handleCommand($command, $this->pbjx);

        foreach ($this->eventStore->pipeAllEvents() as [$event, $streamId]) {
            $this->assertInstanceOf(TeaserSlottingRemovedV1::class, $event);
            $this->assertTrue($nodeRef->equals($event->get('node_ref')));
            $this->assertSame('home', $event->get('slotting_keys')[0]);
            $this->assertTrue(StreamId::fromString("acme:article-teaser:{$nodeRef->getId()}")->equals($streamId));
        }
    }

    public function testHandleCommandNoSlotting(): void
    {
        $teaser = ArticleTeaserV1::create()
            ->set('target_ref', ArticleV1::create()->generateNodeRef())
            ->addToMap('slotting', 'home', 1);
        $ncrSearch = new MockNcrSearch();
        $ncrSearch->indexNodes([$teaser]);
        $this->locator->registerRequestHandler(
            SchemaCurie::fromString('triniti:curator:request:search-teasers-request'),
            new MockSearchNodesRequestHandler($ncrSearch)
        );
        $command = RemoveTeaserSlottingV1::create();
        $handler = new RemoveTeaserSlottingHandler($this->ncr);
        $handler->handleCommand($command, $this->pbjx);

        $eventCount = 0;
        foreach ($this->eventStore->pipeAllEvents() as [$event, $streamId]) {
            $eventCount++;
        }
        $this->assertSame(0, $eventCount);
    }

    public function testHandleCommandNoSlottingConflicts(): void
    {
        $teaser = ArticleTeaserV1::create()
            ->set('target_ref', ArticleV1::create()->generateNodeRef())
            ->addToMap('slotting', 'home', 1);
        $ncrSearch = new MockNcrSearch();
        $ncrSearch->indexNodes([$teaser]);
        $this->locator->registerRequestHandler(
            SchemaCurie::fromString('triniti:curator:request:search-teasers-request'),
            new MockSearchNodesRequestHandler($ncrSearch),
            );
        $command = RemoveTeaserSlottingV1::create()->addToMap('slotting', 'home', 2);
        $handler = new RemoveTeaserSlottingHandler($this->ncr);
        $handler->handleCommand($command, $this->pbjx);

        $eventCount = 0;
        foreach ($this->eventStore->pipeAllEvents() as [$event, $streamId]) {
            $eventCount++;
        }
        $this->assertSame(0, $eventCount);
    }
}
