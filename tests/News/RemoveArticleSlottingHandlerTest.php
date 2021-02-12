<?php
declare(strict_types=1);

namespace Triniti\Tests\News;

use Acme\Schemas\News\Command\RemoveArticleSlottingV1;
use Acme\Schemas\News\Node\ArticleV1;
use Gdbots\Ncr\AggregateResolver;
use Gdbots\Pbj\SchemaCurie;
use Gdbots\Schemas\Pbjx\StreamId;
use Triniti\News\RemoveArticleSlottingHandler;
use Triniti\Schemas\News\Event\ArticleSlottingRemovedV1;
use Triniti\Tests\AbstractPbjxTest;
use Triniti\Tests\MockNcrSearch;
use Triniti\Tests\MockSearchNodesRequestHandler;

final class RemoveArticleSlottingHandlerTest extends AbstractPbjxTest
{
    public function testHandleCommand(): void
    {
        $article = ArticleV1::create()->addToMap('slotting', 'home', 1);
        $nodeRef = $article->generateNodeRef();
        $ncrSearch = new MockNcrSearch();
        $ncrSearch->indexNodes([$article]);
        $this->locator->registerRequestHandler(
            SchemaCurie::fromString('triniti:news:request:search-articles-request'),
            new MockSearchNodesRequestHandler($ncrSearch),
        );
        AggregateResolver::register(['acme:article' => 'Triniti\News\ArticleAggregate']);
        $command = RemoveArticleSlottingV1::create()->addToMap('slotting', 'home', 1);
        $handler = new RemoveArticleSlottingHandler();
        $handler->handleCommand($command, $this->pbjx);

        foreach ($this->eventStore->pipeAllEvents() as [$event, $streamId]) {
            $this->assertInstanceOf(ArticleSlottingRemovedV1::class, $event);
            $this->assertTrue($nodeRef->equals($event->get('node_ref')));
            $this->assertSame('home', $event->get('slotting_keys')[0]);
            $this->assertTrue(StreamId::fromString("acme:article:{$nodeRef->getId()}")->equals($streamId));
        }
    }

    public function testHandleCommandNoSlotting(): void
    {
        $article = ArticleV1::create()->addToMap('slotting', 'home', 1);
        $ncrSearch = new MockNcrSearch();
        $ncrSearch->indexNodes([$article]);
        $this->locator->registerRequestHandler(
            SchemaCurie::fromString('triniti:news:request:search-articles-request'),
            new MockSearchNodesRequestHandler($ncrSearch),
            );
        AggregateResolver::register(['acme:article' => 'Triniti\News\ArticleAggregate']);
        $command = RemoveArticleSlottingV1::create();
        $handler = new RemoveArticleSlottingHandler();
        $handler->handleCommand($command, $this->pbjx);

        $eventCount = 0;
        foreach ($this->eventStore->pipeAllEvents() as [$event, $streamId]) {
            $eventCount++;
        }
        $this->assertSame(0, $eventCount);
    }

    public function testHandleCommandNoSlottingConflicts(): void
    {
        $article = ArticleV1::create()->addToMap('slotting', 'home', 1);
        $ncrSearch = new MockNcrSearch();
        $ncrSearch->indexNodes([$article]);
        $this->locator->registerRequestHandler(
            SchemaCurie::fromString('triniti:news:request:search-articles-request'),
            new MockSearchNodesRequestHandler($ncrSearch),
            );
        AggregateResolver::register(['acme:article' => 'Triniti\News\ArticleAggregate']);
        $command = RemoveArticleSlottingV1::create()->addToMap('slotting', 'home', 2);
        $handler = new RemoveArticleSlottingHandler();
        $handler->handleCommand($command, $this->pbjx);

        $eventCount = 0;
        foreach ($this->eventStore->pipeAllEvents() as [$event, $streamId]) {
            $eventCount++;
        }
        $this->assertSame(0, $eventCount);
    }
}
