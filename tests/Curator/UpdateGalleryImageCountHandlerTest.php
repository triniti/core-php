<?php
declare(strict_types=1);

namespace Triniti\Tests\Curator;

use Acme\Schemas\Curator\Command\UpdateGalleryImageCountV1;
use Acme\Schemas\Curator\Event\GalleryImageCountUpdatedV1;
use Acme\Schemas\Curator\Node\GalleryV1;;
use Gdbots\Ncr\AggregateResolver;
use Gdbots\Ncr\Repository\InMemoryNcr;
use Gdbots\Pbj\SchemaCurie;
use Gdbots\Schemas\Pbjx\StreamId;
use Triniti\Curator\UpdateGalleryImageCountHandler;
use Triniti\Tests\AbstractPbjxTest;
use Triniti\Tests\MockNcrSearch;
use Triniti\Tests\MockSearchNodesRequestHandler;

final class UpdateGalleryImageCountHandlerTest extends AbstractPbjxTest
{
    protected InMemoryNcr $ncr;

    public function setup(): void
    {
        parent::setup();
        $this->ncr = new InMemoryNcr();
    }

    public function testHandleCommand(): void
    {
        $this->locator->registerRequestHandler(
            SchemaCurie::fromString('triniti:dam:request:search-assets-request'),
            new MockSearchNodesRequestHandler(new MockNcrSearch())
        );
        $node = GalleryV1::create()->set('image_count', 20);
        $nodeRef = $node->generateNodeRef();
        $this->ncr->putNode($node);
        AggregateResolver::register(['acme:gallery' => 'Triniti\Curator\GalleryAggregate']);
        $command = UpdateGalleryImageCountV1::create()->set('node_ref', $nodeRef);
        $handler = new UpdateGalleryImageCountHandler($this->ncr);
        $handler->handleCommand($command, $this->pbjx);

        foreach ($this->eventStore->pipeAllEvents() as [$event, $streamId]) {
            $this->assertInstanceOf(GalleryImageCountUpdatedV1::class, $event);
            $this->assertTrue($event->get('node_ref')->equals($nodeRef));
            $this->assertSame(0, $event->get('image_count'));
            $this->assertTrue(StreamId::fromString("acme:gallery:{$nodeRef->getId()}")->equals($streamId));
        }
    }

    public function testHandleCommandWithMatchingCount(): void
    {
        $this->locator->registerRequestHandler(
            SchemaCurie::fromString('triniti:dam:request:search-assets-request'),
            new MockSearchNodesRequestHandler(new MockNcrSearch())
        );
        $node = GalleryV1::create()->set('image_count', 0);
        $nodeRef = $node->generateNodeRef();
        $this->ncr->putNode($node);
        AggregateResolver::register(['acme:gallery' => 'Triniti\Curator\GalleryAggregate']);
        $command = UpdateGalleryImageCountV1::create()->set('node_ref', $nodeRef);
        $handler = new UpdateGalleryImageCountHandler($this->ncr);
        $handler->handleCommand($command, $this->pbjx);

        $eventCount = 0;
        foreach ($this->eventStore->pipeAllEvents() as $yield) {
            $eventCount ++;
        }
        $this->assertSame(0, $eventCount);
    }
}
