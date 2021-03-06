<?php
declare(strict_types=1);

namespace Triniti\Tests\Ovp;

use Acme\Schemas\Dam\Node\DocumentAssetV1;
use Acme\Schemas\Dam\Node\VideoAssetV1;
use Acme\Schemas\Ovp\Node\VideoV1;
use Gdbots\Ncr\AggregateResolver;
use Gdbots\Ncr\Repository\InMemoryNcr;
use Gdbots\Schemas\Pbjx\StreamId;
use Triniti\Dam\DocumentAssetAggregate;
use Triniti\Dam\VideoAssetAggregate;
use Triniti\Ovp\UpdateTranscriptionStatusHandler;
use Triniti\Ovp\VideoAggregate;
use Triniti\Schemas\Dam\AssetId;
use Triniti\Schemas\Ovp\Command\UpdateTranscodingStatusV1;
use Triniti\Schemas\Ovp\Command\UpdateTranscriptionStatusV1;
use Triniti\Schemas\Ovp\Enum\TranscriptionStatus;
use Triniti\Schemas\Ovp\Event\TranscriptionCompletedV1;
use Triniti\Schemas\Ovp\Event\TranscriptionFailedV1;
use Triniti\Schemas\Ovp\Event\TranscriptionStartedV1;
use Triniti\Tests\AbstractPbjxTest;

final class UpdateTranscriptionStatusHandlerTest extends AbstractPbjxTest
{
    protected UpdateTranscriptionStatusHandler $handler;
    protected InMemoryNcr $ncr;

    public function setup(): void
    {
        parent::setup();
        $this->ncr = new InMemoryNcr();

        $this->handler = new UpdateTranscriptionStatusHandler($this->ncr);
        AggregateResolver::register(['acme:video' => VideoAggregate::class]);
        AggregateResolver::register(['acme:video-asset' => VideoAssetAggregate::class]);
        AggregateResolver::register(['acme:document-asset' => DocumentAssetAggregate::class]);
    }

    public function testHandleNonVideoAsset(): void
    {
        $node = DocumentAssetV1::fromArray([
            '_id'       => AssetId::create('document', 'vtt'),
            'mime_type' => 'text/vtt',
        ]);
        $command = UpdateTranscriptionStatusV1::create()->set('node_ref', $node->generateNodeRef());
        $this->handler->handleCommand($command, $this->pbjx);

        $eventCount = 0;
        foreach ($this->eventStore->pipeAllEvents() as $yield) {
            $eventCount++;
        }
        $this->assertSame(0, $eventCount);
    }

    public function testHandleEmptyCommand(): void
    {
        $node = VideoAssetV1::fromArray([
            '_id'       => AssetId::create('video', 'mxf'),
            'mime_type' => 'application/mxf',
        ]);
        $command = UpdateTranscodingStatusV1::create()->set('node_ref', $node->generateNodeRef());
        $this->handler->handleCommand($command, $this->pbjx);

        $eventCount = 0;
        foreach ($this->eventStore->pipeAllEvents() as $yield) {
            $eventCount++;
        }
        $this->assertSame(0, $eventCount);
    }

    public function testHandleCanceled(): void
    {
        $node = VideoAssetV1::fromArray([
            '_id'       => AssetId::create('video', 'mxf'),
            'mime_type' => 'application/mxf',
        ]);
        $nodeRef = $node->generateNodeRef();
        $this->ncr->putNode($node);
        $command = UpdateTranscriptionStatusV1::create()
            ->set('node_ref', $nodeRef)
            ->set('transcription_status', TranscriptionStatus::CANCELED());
        $this->handler->handleCommand($command, $this->pbjx);

        $streamId = StreamId::fromNodeRef($nodeRef);
        foreach ($this->eventStore->pipeEvents($streamId) as $event) {
            $this->assertTrue($nodeRef->equals($event->get('node_ref')));
            $this->assertInstanceOf(TranscriptionFailedV1::class, $event);
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
            'title'     => 'foo',
        ]);
        $videoAsset->addToSet('linked_refs', [$videoRef]);
        $videoAssetRef = $videoAsset->generateNodeRef();
        $this->ncr->putNode($videoAsset);

        $documentAsset = DocumentAssetV1::fromArray([
            '_id'       => 'document_vtt_' . $videoAssetId->getDate() . '_' . $videoAssetId->getUuid(),
            'mime_type' => 'text/vtt',
        ]);
        $documentAssetRef = $documentAsset->generateNodeRef();
        $this->ncr->putNode($documentAsset);

        $command = UpdateTranscriptionStatusV1::create()
            ->set('node_ref', $videoAssetRef)
            ->set('transcription_status', TranscriptionStatus::COMPLETED());
        $this->handler->handleCommand($command, $this->pbjx);

        $videoAssetStreamId = StreamId::fromNodeRef($videoAssetRef);
        foreach ($this->eventStore->pipeEvents($videoAssetStreamId) as $event) {
            $this->assertTrue($videoAssetRef->equals($event->get('node_ref')));
            $this->assertInstanceOf(TranscriptionCompletedV1::class, $event);
        }

        $documentAssetStreamId = StreamId::fromNodeRef($documentAssetRef);
        foreach ($this->eventStore->pipeEvents($documentAssetStreamId) as $event) {
            $this->assertTrue($documentAssetRef->equals($event->get('node_ref')));
            $this->assertInstanceOf(TranscriptionCompletedV1::class, $event);
            $this->assertTrue($event->getFromMap('tags', 'video_asset_title') === $videoAsset->get('title'));
        }

        $videoStreamId = StreamId::fromNodeRef($videoRef);
        foreach ($this->eventStore->pipeEvents($videoStreamId) as $event) {
            $this->assertTrue($videoRef->equals($event->get('node_ref')));
            $this->assertInstanceOf(TranscriptionCompletedV1::class, $event);
            $this->assertTrue($event->getFromMap('tags', 'document_asset_ref') === $documentAssetRef->toString());
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
        $command = UpdateTranscriptionStatusV1::create()
            ->set('node_ref', $videoAssetRef)
            ->set('transcription_status', TranscriptionStatus::FAILED());
        $this->handler->handleCommand($command, $this->pbjx);

        $streamId = StreamId::fromNodeRef($videoAssetRef);
        foreach ($this->eventStore->pipeEvents($streamId) as $event) {
            $this->assertTrue($videoAssetRef->equals($event->get('node_ref')));
            $this->assertInstanceOf(TranscriptionFailedV1::class, $event);
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
        $command = UpdateTranscriptionStatusV1::create()
            ->set('node_ref', $videoAssetRef)
            ->set('transcription_status', TranscriptionStatus::PROCESSING());
        $this->handler->handleCommand($command, $this->pbjx);

        $streamId = StreamId::fromNodeRef($videoAssetRef);
        foreach ($this->eventStore->pipeEvents($streamId) as $event) {
            $this->assertTrue($videoAssetRef->equals($event->get('node_ref')));
            $this->assertInstanceOf(TranscriptionStartedV1::class, $event);
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
        $command = UpdateTranscriptionStatusV1::create()
            ->set('node_ref', $videoAssetRef)
            ->set('transcription_status', TranscriptionStatus::UNKNOWN());
        $this->handler->handleCommand($command, $this->pbjx);

        $streamId = StreamId::fromNodeRef($videoAssetRef);
        foreach ($this->eventStore->pipeEvents($streamId) as $event) {
            $this->assertTrue($videoAssetRef->equals($event->get('node_ref')));
            $this->assertInstanceOf(TranscriptionFailedV1::class, $event);
        }
    }
}
