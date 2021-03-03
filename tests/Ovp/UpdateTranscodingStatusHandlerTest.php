<?php
declare(strict_types=1);

namespace Triniti\Tests\Ovp;

use Acme\Schemas\Dam\Node\DocumentAssetV1;
use Acme\Schemas\Dam\Node\ImageAssetV1;
use Acme\Schemas\Dam\Node\VideoAssetV1;
use Acme\Schemas\Ovp\Node\VideoV1;
use Gdbots\Ncr\AggregateResolver;
use Gdbots\Ncr\Repository\InMemoryNcr;
use Gdbots\Schemas\Pbjx\StreamId;
use Triniti\Dam\VideoAssetAggregate;
use Triniti\Ovp\UpdateTranscodingStatusHandler;
use Triniti\Ovp\VideoAggregate;
use Triniti\Schemas\Dam\AssetId;
use Triniti\Schemas\Ovp\Command\UpdateTranscodingStatusV1;
use Triniti\Schemas\Ovp\Enum\TranscodingStatus;
use Triniti\Schemas\Ovp\Event\TranscodingCompletedV1;
use Triniti\Schemas\Ovp\Event\TranscodingFailedV1;
use Triniti\Schemas\Ovp\Event\TranscodingStartedV1;
use Triniti\Tests\AbstractPbjxTest;

final class UpdateTranscodingStatusHandlerTest extends AbstractPbjxTest
{
    private UpdateTranscodingStatusHandler $handler;
    protected InMemoryNcr $ncr;

    public function setup(): void
    {
        parent::setup();
        $this->ncr = new InMemoryNcr();

        AggregateResolver::register(['acme:video-asset' => VideoAssetAggregate::class]);
        AggregateResolver::register(['acme:video' => VideoAggregate::class]);
        $this->handler = new UpdateTranscodingStatusHandler($this->ncr);
    }

    public function testHandleNonVideoAsset(): void
    {
        $command = UpdateTranscodingStatusV1::create()->set('node_ref', DocumentAssetV1::create()->generateNodeRef());
        $this->handler->handleCommand($command, $this->pbjx);

        $eventCount = 0;
        foreach ($this->eventStore->pipeAllEvents() as $yield) {
            $eventCount++;
        }
        $this->assertSame(0, $eventCount);
    }

    public function testHandleEmptyCommand(): void
    {
        $command = UpdateTranscodingStatusV1::create()->set('node_ref', VideoAssetV1::create()->generateNodeRef());
        $this->handler->handleCommand($command, $this->pbjx);

        $eventCount = 0;
        foreach ($this->eventStore->pipeAllEvents() as $yield) {
            $eventCount++;
        }
        $this->assertSame(0, $eventCount);
    }

    public function testHandleCanceled(): void
    {
        $videoAsset = VideoAssetV1::fromArray([
            '_id'       => AssetId::create('video', 'mxf'),
            'mime_type' => 'application/mxf',
        ]);
        $this->ncr->putNode($videoAsset);
        $videoAssetRef = $videoAsset->generateNodeRef();
        $command = UpdateTranscodingStatusV1::create()
            ->set('node_ref', $videoAssetRef)
            ->set('transcoding_status', TranscodingStatus::CANCELED());
        $this->handler->handleCommand($command, $this->pbjx);

        $streamId = StreamId::fromNodeRef($videoAssetRef);
        foreach ($this->eventStore->pipeEvents($streamId) as $event) {
            $this->assertInstanceOf(TranscodingFailedV1::class, $event);
            $this->assertTrue($event->get('node_ref')->equals($videoAssetRef));
        }
    }

    public function testHandleCompleted(): void
    {
        $video = VideoV1::create();
        $videoRef = $video->generateNodeRef();
        $this->ncr->putNode($video);

        $videoAssetId = AssetId::create('video', 'mxf');
        $videoAsset = VideoAssetV1::fromArray([
            '_id'       => $videoAssetId,
            'mime_type' => 'application/mxf',
        ]);
        $videoAsset->addToSet('linked_refs', [$videoRef]);
        $videoAssetRef = $videoAsset->generateNodeRef();
        $this->ncr->putNode($videoAsset);

        $imageAsset = ImageAssetV1::fromArray([
            '_id'       => 'image_jpg_' . $videoAssetId->getDate() . '_' . $videoAssetId->getUuid(),
            'mime_type' => 'image/jpeg',
        ]);
        $imageAssetRef = $imageAsset->generateNodeRef();

        $command = UpdateTranscodingStatusV1::create()
            ->set('node_ref', $videoAssetRef)
            ->set('transcoding_status', TranscodingStatus::COMPLETED())
            ->set('mediaconvert_job_arn', 'foo')
            ->set('mediaconvert_queue_arn', 'bar');
        $this->handler->handleCommand($command, $this->pbjx);

        $videoAssetStreamId = StreamId::fromNodeRef($videoAssetRef);
        foreach ($this->eventStore->pipeEvents($videoAssetStreamId) as $event) {
            $this->assertInstanceOf(TranscodingCompletedV1::class, $event);
            $this->assertTrue($event->get('node_ref')->equals($videoAssetRef));

            $actual = $event->get('mediaconvert_job_arn');
            $expected = 'foo';
            $this->assertSame($actual, $expected);

            $actual = $event->get('mediaconvert_queue_arn');
            $expected = 'bar';
            $this->assertSame($actual, $expected);
        }

        $videoStreamId = StreamId::fromNodeRef($videoRef);
        foreach ($this->eventStore->pipeEvents($videoStreamId) as $event) {
            $this->assertInstanceOf(TranscodingCompletedV1::class, $event);
            $this->assertTrue($event->get('node_ref')->equals($videoRef));

            $actual = $event->get('mediaconvert_job_arn');
            $expected = 'foo';
            $this->assertSame($actual, $expected);

            $actual = $event->get('mediaconvert_queue_arn');
            $expected = 'bar';
            $this->assertSame($actual, $expected);

            $actual = $event->getFromMap('tags', 'video_asset_ref');
            $expected = $videoAssetRef->toString();
            $this->assertSame($actual, $expected);

            $actual = $event->getFromMap('tags', 'image_asset_ref');
            $expected = $imageAssetRef->toString();
            $this->assertSame($actual, $expected);
        }
    }

    public function testHandleFailed(): void
    {
        $videoAsset = VideoAssetV1::fromArray([
            '_id'       => AssetId::create('video', 'mxf'),
            'mime_type' => 'application/mxf',
        ]);
        $this->ncr->putNode($videoAsset);
        $videoAssetRef = $videoAsset->generateNodeRef();
        $command = UpdateTranscodingStatusV1::create()
            ->set('node_ref', $videoAssetRef)
            ->set('transcoding_status', TranscodingStatus::FAILED());
        $this->handler->handleCommand($command, $this->pbjx);

        $streamId = StreamId::fromNodeRef($videoAssetRef);
        foreach ($this->eventStore->pipeEvents($streamId) as $event) {
            $this->assertInstanceOf(TranscodingFailedV1::class, $event);
            $this->assertTrue($event->get('node_ref')->equals($videoAssetRef));
        }
    }

    public function testHandleProcessing(): void
    {
        $videoAsset = VideoAssetV1::fromArray([
            '_id'       => AssetId::create('video', 'mxf'),
            'mime_type' => 'application/mxf',
        ]);
        $this->ncr->putNode($videoAsset);
        $videoAssetRef = $videoAsset->generateNodeRef();
        $command = UpdateTranscodingStatusV1::create()
            ->set('node_ref', $videoAssetRef)
            ->set('transcoding_status', TranscodingStatus::PROCESSING());
        $this->handler->handleCommand($command, $this->pbjx);

        $streamId = StreamId::fromNodeRef($videoAssetRef);
        foreach ($this->eventStore->pipeEvents($streamId) as $event) {
            $this->assertInstanceOf(TranscodingStartedV1::class, $event);
            $this->assertTrue($event->get('node_ref')->equals($videoAssetRef));
        }
    }

    public function testHandleUnknown(): void
    {
        $videoAsset = VideoAssetV1::fromArray([
            '_id'       => AssetId::create('video', 'mxf'),
            'mime_type' => 'application/mxf',
        ]);
        $this->ncr->putNode($videoAsset);
        $videoAssetRef = $videoAsset->generateNodeRef();
        $command = UpdateTranscodingStatusV1::create()
            ->set('node_ref', $videoAssetRef)
            ->set('transcoding_status', TranscodingStatus::UNKNOWN());
        $this->handler->handleCommand($command, $this->pbjx);

        $streamId = StreamId::fromNodeRef($videoAssetRef);
        foreach ($this->eventStore->pipeEvents($streamId) as $event) {
            $this->assertInstanceOf(TranscodingFailedV1::class, $event);
            $this->assertTrue($event->get('node_ref')->equals($videoAssetRef));
        }
    }
}
