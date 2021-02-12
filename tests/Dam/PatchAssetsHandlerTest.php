<?php
declare(strict_types=1);

namespace Triniti\Tests\Dam;

use Acme\Schemas\Dam\Command\PatchAssetsV1;
use Acme\Schemas\Dam\Node\ImageAssetV1;
use Gdbots\Schemas\Pbjx\StreamId;
use Triniti\Dam\PatchAssetsHandler;
use Triniti\Schemas\Dam\AssetId;
use Triniti\Schemas\Dam\Event\AssetPatchedV1;
use Triniti\Tests\AbstractPbjxTest;

final class PatchAssetsHandlerTest extends AbstractPbjxTest
{
    public function testHandleCommand(): void
    {
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

        $paths = ['title', 'expires_at', 'credit', 'description'];
        $title = 'Possible Title';
        $credit = 'Possible Vendor';
        $expiresAt = new \DateTime();
        $description = 'Possible Description';

        $command = PatchAssetsV1::create()
            ->addToSet('node_refs', [$asset1->generateNodeRef(), $asset2->generateNodeRef()])
            ->addToSet('paths', $paths)
            ->set('title', $title)
            ->set('credit', $credit)
            ->set('expires_at', $expiresAt)
            ->set('description', $description);

        $handler = new PatchAssetsHandler($this->ncr);
        $handler->handleCommand($command, $this->pbjx);

        foreach ($this->pbjx->getEventStore()->pipeEvents(StreamId::fromNodeRef($asset1Ref)) as $event) {
            $this->assertInstanceOf(AssetPatchedV1::class, $event);
            $this->assertEquals($paths, $event->get('paths'));
            $this->assertTrue($asset1Ref->equals($event->get('node_ref')));
            $this->assertSame($title, $event->get('title'));
            $this->assertSame($credit, $event->get('credit'));
            $this->assertSame($expiresAt, $event->get('expires_at'));
            $this->assertSame($description, $event->get('description'));
        }

        foreach ($this->pbjx->getEventStore()->pipeEvents(StreamId::fromNodeRef($asset2Ref)) as $event) {
            $this->assertInstanceOf(AssetPatchedV1::class, $event);
            $this->assertEquals($paths, $event->get('paths'));
            $this->assertTrue($asset2Ref->equals($event->get('node_ref')));
            $this->assertSame($title, $event->get('title'));
            $this->assertSame($credit, $event->get('credit'));
            $this->assertSame($expiresAt, $event->get('expires_at'));
            $this->assertSame($description, $event->get('description'));
        }
    }
}
