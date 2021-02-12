<?php
declare(strict_types=1);

namespace Triniti\Tests\Dam;

use Acme\Schemas\Dam\Command\LinkAssetsV1;
use Acme\Schemas\Dam\Node\ImageAssetV1;
use Gdbots\Ncr\AggregateResolver;
use Gdbots\Ncr\Repository\InMemoryNcr;
use Gdbots\Pbj\WellKnown\NodeRef;
use Gdbots\Schemas\Pbjx\StreamId;
use Triniti\Dam\LinkAssetsHandler;
use Triniti\Schemas\Dam\AssetId;
use Triniti\Schemas\Dam\Event\AssetLinkedV1;
use Triniti\Tests\AbstractPbjxTest;

final class LinkAssetsHandlerTest extends AbstractPbjxTest
{
    private InMemoryNcr $ncr;

    protected function setup(): void
    {
        parent::setup();
        $this->ncr = new InMemoryNcr();
    }

    public function testHandle(): void
    {
        $nodeRef = NodeRef::fromString('acme:article:56eac630-d499-4865-90c9-7018299cd2aa');

        $asset1 = ImageAssetV1::fromArray([
            '_id'       => AssetId::create('image', 'jpg'),
            'mime_type' => 'image/jpeg',
        ]);
        $asset1Ref = $asset1->generateNodeRef();
        $this->ncr->putNode($asset1);
        $asset2 = ImageAssetV1::fromArray([
            '_id'       => AssetId::create('image', 'jpg'),
            'mime_type' => 'image/jpeg',
        ]);
        $asset2Ref = $asset2->generateNodeRef();
        $this->ncr->putNode($asset2);

        AggregateResolver::register(['acme:image-asset' => 'Triniti\Dam\AssetAggregate']);
        $handler = new LinkAssetsHandler($this->ncr);
        $command = LinkAssetsV1::create()
            ->set('node_ref', $nodeRef)
            ->addToSet('asset_refs', [$asset1Ref, $asset2Ref]);
        $handler->handleCommand($command, $this->pbjx);

        foreach ($this->pbjx->getEventStore()->pipeEvents(StreamId::fromNodeRef($asset1Ref)) as $event) {
            $this->assertInstanceOf(AssetLinkedV1::class, $event);
            $this->assertTrue($event->get('node_ref')->equals($asset1Ref));
            $this->assertTrue($event->get('linked_ref')->equals($nodeRef));
        }

        foreach ($this->pbjx->getEventStore()->pipeEvents(StreamId::fromNodeRef($asset2Ref)) as $event) {
            $this->assertInstanceOf(AssetLinkedV1::class, $event);
            $this->assertTrue($event->get('node_ref')->equals($asset2Ref));
            $this->assertTrue($event->get('linked_ref')->equals($nodeRef));
        }
    }
}
