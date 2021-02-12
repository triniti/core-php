<?php
declare(strict_types=1);

namespace Triniti\Tests\Dam;

use Acme\Schemas\Dam\Command\ReorderGalleryAssetsV1;
use Acme\Schemas\Dam\Node\ImageAssetV1;
use Gdbots\Pbj\WellKnown\NodeRef;
use Gdbots\Schemas\Pbjx\StreamId;
use Triniti\Dam\ReorderGalleryAssetsHandler;
use Triniti\Schemas\Dam\AssetId;
use Triniti\Schemas\Dam\Event\GalleryAssetReorderedV1;
use Triniti\Tests\AbstractPbjxTest;

final class ReorderGalleryAssetsHandlerTest extends AbstractPbjxTest
{
    public function testHandleCommand(): void
    {
        $oldGalleryNodeRef = NodeRef::fromString('acme:gallery:123s');
        $galleryNodeRef = NodeRef::fromString('acme:gallery:56eac630-d499-4865-90c9-7018299cd2aa');

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

        $sequenceNumbers = [500, 1000];

        $command = ReorderGalleryAssetsV1::create()
            ->set('gallery_ref', $galleryNodeRef)
            ->addToMap('gallery_seqs', $asset1->get('_id')->toString(), $sequenceNumbers[0])
            ->addToMap('old_gallery_refs', $asset1->get('_id')->toString(), $oldGalleryNodeRef)
            ->addToMap('gallery_seqs', $asset2->get('_id')->toString(), $sequenceNumbers[1])
            ->addToMap('old_gallery_refs', $asset2->get('_id')->toString(), $oldGalleryNodeRef);

        $handler = new ReorderGalleryAssetsHandler($this->ncr);
        $handler->handleCommand($command, $this->pbjx);

        foreach ($this->pbjx->getEventStore()->pipeEvents(StreamId::fromNodeRef($asset1Ref)) as $event) {
            $this->assertInstanceOf(GalleryAssetReorderedV1::class, $event);
            $this->assertTrue($asset1Ref->equals($event->get('node_ref')));
            $this->assertSame($sequenceNumbers[0], $event->get('gallery_seq'));
            $this->assertTrue($galleryNodeRef->equals($event->get('gallery_ref')));
            $this->assertTrue($oldGalleryNodeRef->equals($event->get('old_gallery_ref')));
        }

        foreach ($this->pbjx->getEventStore()->pipeEvents(StreamId::fromNodeRef($asset2Ref)) as $event) {
            $this->assertInstanceOf(GalleryAssetReorderedV1::class, $event);
            $this->assertTrue($asset2Ref->equals($event->get('node_ref')));
            $this->assertSame($sequenceNumbers[1], $event->get('gallery_seq'));
            $this->assertTrue($galleryNodeRef->equals($event->get('gallery_ref')));
            $this->assertTrue($oldGalleryNodeRef->equals($event->get('old_gallery_ref')));
        }
    }
}
