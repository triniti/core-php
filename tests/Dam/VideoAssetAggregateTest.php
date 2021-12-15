<?php
declare(strict_types=1);

namespace Triniti\Tests\Dam;

use Acme\Schemas\Dam\Node\VideoAssetV1;
use Gdbots\Schemas\Pbjx\Enum\Code;
use Triniti\Dam\VideoAssetAggregate;
use Triniti\Schemas\Dam\AssetId;
use Triniti\Schemas\Ovp\Command\UpdateTranscodingStatusV1;
use Triniti\Schemas\Ovp\Enum\TranscodingStatus;
use Triniti\Tests\AbstractPbjxTest;

final class VideoAssetAggregateTest extends AbstractPbjxTest
{
    public function testUpdateTranscodingStatusCanceled(): void
    {
        $asset = VideoAssetV1::fromArray([
            '_id'       => AssetId::create('video', 'mp4'),
            'mime_type' => 'application/mxf',
        ]);
        $errorCode = Code::ALREADY_EXISTS;
        $command = UpdateTranscodingStatusV1::create()
            ->set('node_ref', $asset->generateNodeRef())
            ->set('mediaconvert_job_arn', 'foo')
            ->set('mediaconvert_queue_arn', 'bar')
            ->set('transcoding_status', TranscodingStatus::CANCELED)
            ->set('error_code', $errorCode);
        $aggregate = VideoAssetAggregate::fromNode($asset, $this->pbjx);
        $aggregate->sync();
        $aggregate->updateTranscodingStatus($command);
        $aggregate->commit();
        $aggregateNode = $aggregate->getNode();
        $this->assertSame($aggregateNode->getFromMap('tags', 'mediaconvert_job_arn'), 'foo');
        $this->assertSame($aggregateNode->getFromMap('tags', 'mediaconvert_queue_arn'), 'bar');
        $this->assertSame($aggregateNode->getFromMap('tags', 'transcode_error_name'), $errorCode->name);
        $this->assertSame($aggregateNode->getFromMap('tags', 'transcode_error_code'), (string)$errorCode->value);
        $this->assertTrue($aggregateNode->get('transcoding_status') === TranscodingStatus::FAILED);
    }

    public function testUpdateTranscodingStatusFailed(): void
    {
        $asset = VideoAssetV1::fromArray([
            '_id'       => AssetId::create('video', 'mp4'),
            'mime_type' => 'application/mxf',
        ]);
        $errorCode = Code::ALREADY_EXISTS;
        $command = UpdateTranscodingStatusV1::create()
            ->set('node_ref', $asset->generateNodeRef())
            ->set('mediaconvert_job_arn', 'foo')
            ->set('mediaconvert_queue_arn', 'bar')
            ->set('transcoding_status', TranscodingStatus::FAILED)
            ->set('error_code', $errorCode);
        $aggregate = VideoAssetAggregate::fromNode($asset, $this->pbjx);
        $aggregate->sync();
        $aggregate->updateTranscodingStatus($command);
        $aggregate->commit();
        $aggregateNode = $aggregate->getNode();
        $this->assertSame($aggregateNode->getFromMap('tags', 'mediaconvert_job_arn'), 'foo');
        $this->assertSame($aggregateNode->getFromMap('tags', 'mediaconvert_queue_arn'), 'bar');
        $this->assertSame($aggregateNode->getFromMap('tags', 'transcode_error_name'), $errorCode->name);
        $this->assertSame($aggregateNode->getFromMap('tags', 'transcode_error_code'), (string)$errorCode->value);
        $this->assertTrue($aggregateNode->get('transcoding_status') === TranscodingStatus::FAILED);
    }

    public function testUpdateTranscodingStatusUnknown(): void
    {
        $asset = VideoAssetV1::fromArray([
            '_id'       => AssetId::create('video', 'mp4'),
            'mime_type' => 'application/mxf',
        ]);
        $errorCode = Code::ALREADY_EXISTS;
        $command = UpdateTranscodingStatusV1::create()
            ->set('node_ref', $asset->generateNodeRef())
            ->set('mediaconvert_job_arn', 'foo')
            ->set('mediaconvert_queue_arn', 'bar')
            ->set('transcoding_status', TranscodingStatus::UNKNOWN)
            ->set('error_code', $errorCode);
        $aggregate = VideoAssetAggregate::fromNode($asset, $this->pbjx);
        $aggregate->sync();
        $aggregate->updateTranscodingStatus($command);
        $aggregate->commit();
        $aggregateNode = $aggregate->getNode();
        $this->assertSame($aggregateNode->getFromMap('tags', 'mediaconvert_job_arn'), 'foo');
        $this->assertSame($aggregateNode->getFromMap('tags', 'mediaconvert_queue_arn'), 'bar');
        $this->assertSame($aggregateNode->getFromMap('tags', 'transcode_error_name'), $errorCode->name);
        $this->assertSame($aggregateNode->getFromMap('tags', 'transcode_error_code'), (string)$errorCode->value);
        $this->assertTrue($aggregateNode->get('transcoding_status') === TranscodingStatus::FAILED);
    }

    public function testUpdateTranscodingStatusCompleted(): void
    {
        $asset = VideoAssetV1::fromArray([
            '_id'       => AssetId::create('video', 'mp4'),
            'mime_type' => 'application/mxf',
        ]);
        $errorCode = Code::ALREADY_EXISTS;
        $command = UpdateTranscodingStatusV1::create()
            ->set('node_ref', $asset->generateNodeRef())
            ->set('mediaconvert_job_arn', 'foo')
            ->set('mediaconvert_queue_arn', 'bar')
            ->set('transcoding_status', TranscodingStatus::COMPLETED)
            ->set('error_code', $errorCode);
        $aggregate = VideoAssetAggregate::fromNode($asset, $this->pbjx);
        $aggregate->sync();
        $aggregate->updateTranscodingStatus($command);
        $aggregate->commit();
        $aggregateNode = $aggregate->getNode();
        $this->assertSame($aggregateNode->getFromMap('tags', 'mediaconvert_job_arn'), 'foo');
        $this->assertSame($aggregateNode->getFromMap('tags', 'mediaconvert_queue_arn'), 'bar');
        $this->assertTrue($aggregateNode->get('transcoding_status') === TranscodingStatus::COMPLETED);
    }

    public function testUpdateTranscodingStatusProcessing(): void
    {
        $asset = VideoAssetV1::fromArray([
            '_id'       => AssetId::create('video', 'mp4'),
            'mime_type' => 'application/mxf',
        ]);
        $errorCode = Code::ALREADY_EXISTS;
        $command = UpdateTranscodingStatusV1::create()
            ->set('node_ref', $asset->generateNodeRef())
            ->set('mediaconvert_job_arn', 'foo')
            ->set('mediaconvert_queue_arn', 'bar')
            ->set('transcoding_status', TranscodingStatus::PROCESSING)
            ->set('error_code', $errorCode);
        $aggregate = VideoAssetAggregate::fromNode($asset, $this->pbjx);
        $aggregate->sync();
        $aggregate->updateTranscodingStatus($command);
        $aggregate->commit();
        $aggregateNode = $aggregate->getNode();
        $this->assertSame($aggregateNode->getFromMap('tags', 'mediaconvert_job_arn'), 'foo');
        $this->assertSame($aggregateNode->getFromMap('tags', 'mediaconvert_queue_arn'), 'bar');
        $this->assertTrue($aggregateNode->get('transcoding_status') === TranscodingStatus::PROCESSING);
    }
}
