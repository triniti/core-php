<?php
declare(strict_types=1);

namespace Triniti\Tests\Dam;

use Acme\Schemas\Dam\Command\UnlinkAssetsV1;
use Acme\Schemas\Dam\Node\ImageAssetV1;
use Gdbots\Pbj\WellKnown\NodeRef;
use Gdbots\Schemas\Pbjx\StreamId;
use Triniti\Dam\UnlinkAssetsHandler;
use Triniti\Schemas\Dam\AssetId;
use Triniti\Schemas\Dam\Event\AssetUnlinkedV1;

final class UnlinkAssetsHandlerTest extends AbstractPbjxTest
{
    public function testHandleCommand(): void
    {
        $nodeRef = NodeRef::fromString('acme:article:56eac630-d499-4865-90c9-7018299cd2aa');

        $asset1 = ImageAssetV1::fromArray([
            '_id'         => AssetId::create('image', 'jpg'),
            'mime_type'   => 'image/jpeg',
            'linked_refs' => [$nodeRef]
        ]);
        $asset1Ref = $asset1->generateNodeRef();
        $this->ncr->putNode($asset1);
        $asset2 = ImageAssetV1::fromArray([
            '_id'         => AssetId::create('image', 'jpg'),
            'mime_type'   => 'image/jpeg',
            'linked_refs' => [$nodeRef]
        ]);
        $asset2Ref = $asset2->generateNodeRef();
        $this->ncr->putNode($asset2);

        $command = UnlinkAssetsV1::create()
            ->set('node_ref', $nodeRef)
            ->addToSet('asset_refs', [$asset1Ref, $asset2Ref]);

        $handler = new UnlinkAssetsHandler($this->ncr);
        $handler->handleCommand($command, $this->pbjx);

        foreach ($this->pbjx->getEventStore()->pipeEvents(StreamId::fromNodeRef($asset1Ref)) as $event) {
            $this->assertInstanceOf(AssetUnlinkedV1::class, $event);
            $this->assertTrue($event->get('node_ref')->equals($asset1Ref));
            $this->assertTrue($event->get('linked_ref')->equals($nodeRef));
        }

        foreach ($this->pbjx->getEventStore()->pipeEvents(StreamId::fromNodeRef($asset2Ref)) as $event) {
            $this->assertInstanceOf(AssetUnlinkedV1::class, $event);
            $this->assertTrue($event->get('node_ref')->equals($asset2Ref));
            $this->assertTrue($event->get('linked_ref')->equals($nodeRef));
        }
    }
}
