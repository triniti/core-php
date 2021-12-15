<?php
declare(strict_types=1);

namespace Triniti\Tests\Ovp;

use Acme\Schemas\Dam\Node\VideoAssetV1;
use Acme\Schemas\Ovp\Node\VideoV1;
use Gdbots\Pbj\WellKnown\NodeRef;
use Triniti\Dam\UrlProvider;
use Triniti\Ovp\ArtifactUrlProvider;
use Triniti\Ovp\VideoAggregate;
use Triniti\Schemas\Dam\AssetId;
use Triniti\Schemas\Ovp\Command\UpdateTranscodingStatusV1;
use Triniti\Schemas\Ovp\Command\UpdateTranscriptionStatusV1;
use Triniti\Schemas\Ovp\Enum\TranscodingStatus;
use Triniti\Schemas\Ovp\Enum\TranscriptionStatus;
use Triniti\Schemas\OvpJwplayer\Command\SyncMediaV1;
use Triniti\Tests\AbstractPbjxTest;

final class VideoAggregateTest extends AbstractPbjxTest
{
    public function testSyncMedia(): void
    {
        $node = VideoV1::create();
        $mediaId = 'baz';
        $aggregate = VideoAggregate::fromNode($node, $this->pbjx);
        $command = SyncMediaV1::create()
            ->set('node_ref', $node->generateNodeRef());
        $aggregate->syncMedia($command, [], $mediaId);
        $aggregate->commit();

        $actual = $aggregate->getNode()->get('jwplayer_media_id');
        $expected = $mediaId;
        $this->assertSame($actual, $expected);
    }

    public function testUpdateTranscodingStatus(): void
    {
        $video = VideoV1::create();
        $videoAsset = VideoAssetV1::fromArray(['_id' => AssetId::create('video', 'mxf')]);
        $videoAssetRef = $videoAsset->generateNodeRef();
        $videoAssetId = AssetId::fromString($videoAssetRef->getId());
        $imageAssetRef = NodeRef::fromString(sprintf(
            '%s:image-asset:image_jpg_%s_%s',
            $videoAssetRef->getVendor(),
            $videoAssetId->getDate(),
            $videoAssetId->getUuid()
        ));
        $command = UpdateTranscodingStatusV1::create()
            ->set('node_ref', $videoAssetRef)
            ->set('transcoding_status', TranscodingStatus::COMPLETED)
            ->set('mediaconvert_job_arn', 'foo')
            ->set('mediaconvert_queue_arn', 'bar');
        $aggregate = VideoAggregate::fromNode($video, $this->pbjx);
        $aggregate->updateTranscodingStatus($command, $videoAsset);
        $aggregate->commit();
        $aggregateNode = $aggregate->getNode();
        $this->assertTrue($videoAssetRef->equals($aggregateNode->get('mezzanine_ref')));
        $this->assertTrue($imageAssetRef->equals($aggregateNode->get('image_ref')));

        $actual = $aggregateNode->get('mezzanine_url');
        $expected = ArtifactUrlProvider::getInstance()->getManifest($videoAssetId);
        $this->assertSame($actual, $expected);

        $actual = $aggregateNode->get('kaltura_mp4_url');
        $expected = ArtifactUrlProvider::getInstance()->getVideo($videoAssetId);
        $this->assertSame($actual, $expected);
    }

    public function testUpdateTranscriptionStatus(): void
    {
        $video = VideoV1::create();
        $videoAsset = VideoAssetV1::fromArray(['_id' => AssetId::create('video', 'mxf')]);
        $videoAssetRef = $videoAsset->generateNodeRef();
        $videoAssetId = AssetId::fromString($videoAssetRef->getId());
        $command = UpdateTranscriptionStatusV1::create()
            ->set('node_ref', $videoAssetRef)
            ->set('transcription_status', TranscriptionStatus::COMPLETED);
        $aggregate = VideoAggregate::fromNode($video, $this->pbjx);
        $aggregate->updateTranscriptionStatus($command, $videoAsset);
        $aggregate->commit();
        $aggregateNode = $aggregate->getNode();

        $documentRef = NodeRef::fromString(sprintf(
            '%s:document-asset:document_vtt_%s_%s',
            $videoAssetRef->getVendor(),
            $videoAssetId->getDate(),
            $videoAssetId->getUuid()
        ));

        $this->assertTrue($documentRef->equals($aggregateNode->get('caption_ref')));

        $actual = $aggregateNode->getFromMap('caption_urls', 'en');
        $expected = UrlProvider::getInstance()->getUrl(AssetId::fromString($documentRef->getId()));
        $this->assertSame($actual, $expected);
    }
}
