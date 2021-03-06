<?php
declare(strict_types=1);

namespace Triniti\Tests\Dam;

use Acme\Schemas\Dam\Node\ImageAssetV1;
use Aws\S3\S3Client;
use Gdbots\Pbjx\Event\PbjxEvent;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Triniti\Dam\AssetEnricher;
use Triniti\Schemas\Dam\AssetId;

final class AssetEnricherTest extends TestCase
{
    public function testEnrichWithS3Object(): void
    {
        $assetId = AssetId::create('image', 'jpg');
        $asset = ImageAssetV1::create()
            ->set('_id', $assetId)
            ->set('mime_type', 'application/octet-stream');
        $pbjxEvent = new PbjxEvent($asset);

        /** @var S3Client|MockObject $s3Client */
        $s3Client = $this->getMockBuilder(S3Client::class)
            ->disableOriginalConstructor()
            ->setMethods(['headObject'])
            ->getMock();
        $s3Client->method('headObject')
            ->willReturn([
                'ETag'          => 'etag',
                'ContentLength' => '1024',
                'ContentType'   => 'image/jpeg',
            ]);

        $enricher = new AssetEnricher($s3Client, 'bucket');
        $enricher->enrichWithS3Object($pbjxEvent);

        $actual = $asset->get('mime_type');
        $expected = 'image/jpeg';
        $this->assertSame($expected, $actual, 'Enriched mime_type should match.');

        $actual = $asset->get('file_etag');
        $expected = 'etag';
        $this->assertSame($expected, $actual, 'Enriched file_etag should match.');

        $actual = (string)$asset->get('file_size');
        $expected = '1024';
        $this->assertSame($expected, $actual, 'Enriched file_size should match.');
    }
}
