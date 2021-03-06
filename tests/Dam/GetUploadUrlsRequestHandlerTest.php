<?php
declare(strict_types=1);

namespace Triniti\Tests\Dam;

use Acme\Schemas\Dam\Request\GetUploadUrlsRequestV1;
use Aws\Command;
use Aws\S3\S3Client;
use GuzzleHttp\Psr7\Request;
use PHPUnit\Framework\MockObject\MockObject;
use Triniti\Dam\Exception\InvalidArgumentException;
use Triniti\Dam\GetUploadUrlsRequestHandler;
use Triniti\Schemas\Dam\AssetId;
use Triniti\Tests\AbstractPbjxTest;

final class GetUploadUrlsRequestHandlerTest extends AbstractPbjxTest
{
    protected GetUploadUrlsRequestHandler $handler;

    public function setup(): void
    {
        parent::setup();

        /** @var S3Client|MockObject $s3Client */
        $s3Client = $this->getMockBuilder(S3Client::class)
            ->disableOriginalConstructor()
            ->getMock();
        $s3Client
            ->method('getCommand')
            ->willReturn(new Command('PutObject'));
        $s3Client
            ->method('createPresignedRequest')
            ->willReturn(new Request('PUT', 'http://someuri.com'));

        $this->handler = new GetUploadUrlsRequestHandler($s3Client, 'some-bucket');
    }

    public function testSingleVariant(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $assetId = AssetId::fromString('image_jpg_20180502_081a88a42d9e4b78a8db5a458e7f8764');
        $request = GetUploadUrlsRequestV1::create()
            ->addToList('files', [
                '/some/path/test-1.zip',
                '/some/path/test-2.zip',
            ])->set('asset_id', $assetId);

        $this->handler->handleRequest($request, $this->pbjx);
    }

    public function testCantOverrideOriginal(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $assetId = AssetId::fromString('image_jpg_20180502_081a88a42d9e4b78a8db5a458e7f8764');
        $quality = 'o';

        $request = GetUploadUrlsRequestV1::create()
            ->set('asset_id', $assetId)
            ->set('version', $quality)
            ->addToList('files', ['/some/path/test.zip']);

        $this->handler->handleRequest($request, $this->pbjx);
    }

    public function testVariantTypeMatch(): void
    {
        $assetId = AssetId::fromString('image_jpg_20180502_081a88a42d9e4b78a8db5a458e7f8764');

        $request = GetUploadUrlsRequestV1::create()
            ->set('asset_id', $assetId)
            ->set('version', '1080')
            ->addToList('files', ['/some/path/test-variant-type-match.zip']);

        $response = $this->handler->handleRequest($request, $this->pbjx);

        $this->assertFalse($response->isInMap('asset_ids', $assetId->toString()));
        $this->assertFalse($response->isInMap('s3_presigned_urls', $assetId->toString()));
    }

    public function testHandleRequest(): void
    {
        $samples = [
            [
                'file'          => '/some/path/test.zip',
                'expected_type' => 'archive',
                'expected_ext'  => 'zip',
            ],
            [
                'file'          => '/some/path/test.mp3',
                'expected_type' => 'audio',
                'expected_ext'  => 'mp3',
            ],
            [
                'file'          => '/some/path/test.js',
                'expected_type' => 'code',
                'expected_ext'  => 'js',
            ],
            [
                'file'          => 'C:\my_other_image.jpg',
                'expected_type' => 'image',
                'expected_ext'  => 'jpg',
            ],
            [
                'file'          => 'C:\my_other_image.jpeg',
                'expected_type' => 'image',
                'expected_ext'  => 'jpeg',
            ],
            [
                'file'          => '/some-path//test/a.png',
                'expected_type' => 'image',
                'expected_ext'  => 'png',
            ],
            [
                'file'          => '/some-path//test/a.gif',
                'expected_type' => 'image',
                'expected_ext'  => 'gif',
            ],
            [
                'file'          => 'C:\my_other_image.txt',
                'expected_type' => 'document',
                'expected_ext'  => 'txt',
            ],
            [
                'file'          => '/a_sdf/my_other_image.xlsx',
                'expected_type' => 'document',
                'expected_ext'  => 'xlsx',
            ],
            [
                'file'          => '/asd-f/my_other_image.doc',
                'expected_type' => 'document',
                'expected_ext'  => 'doc',
            ],
            [
                'file'          => '/asd-f/my_other_image.pdf',
                'expected_type' => 'document',
                'expected_ext'  => 'pdf',
            ],
            [
                'file'          => 'daydream/nation.vtt',
                'expected_type' => 'document',
                'expected_ext'  => 'vtt',
            ],
            [
                'file'          => 'halcyon/and-on-and-on.srt',
                'expected_type' => 'document',
                'expected_ext'  => 'srt',
            ],
            [
                'file'          => 'c:\videos/video.mp4',
                'expected_type' => 'video',
                'expected_ext'  => 'mp4',
            ],
            [
                'file'          => 'c:\videos/video.mpeg',
                'expected_type' => 'video',
                'expected_ext'  => 'mpeg',
            ],
            [
                'file'          => 'c:\videos/video.mov',
                'expected_type' => 'video',
                'expected_ext'  => 'mov',
            ],
            [
                'file'          => 'c:\videos/video.mxf',
                'expected_type' => 'video',
                'expected_ext'  => 'mxf', // this should exist, wtguzzle?
            ],
            [
                'file'          => 'i.have-no-idea.wtf',
                'expected_type' => 'unknown',
                'expected_ext'  => 'wtf',
            ],
            [
                'file'          => 'i.have-no-idea.document',
                'expected_type' => 'unknown',
                'expected_ext'  => 'document',
            ],
        ];

        $request = GetUploadUrlsRequestV1::create();
        $request->addToList('files', array_column($samples, 'file'));

        $response = $this->handler->handleRequest($request, $this->pbjx);

        foreach ($samples as $sample) {
            $fileHash = md5($sample['file']);

            $this->assertTrue(
                $response->isInMap('s3_presigned_urls', $fileHash),
                'Expected hash not found in "s3_presigned_urls" response.'
            );

            $this->assertTrue(
                $response->isInMap('asset_ids', $fileHash),
                'Expected hash not found in "asset_ids" response.'
            );

            /** @var AssetId $assetId */
            $assetId = $response->getFromMap('asset_ids', $fileHash);
            $this->assertSame(
                $sample['expected_ext'],
                $assetId->getExt(),
                "{$sample['file']} ext should match."
            );

            $this->assertSame(
                $sample['expected_type'],
                $assetId->getType(),
                "{$sample['file']} type should match."
            );
        };
    }
}
