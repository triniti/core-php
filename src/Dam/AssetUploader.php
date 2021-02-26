<?php
declare(strict_types=1);

namespace Triniti\Dam;

use Aws\S3\S3Client;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\ClientInterface as GuzzleClientInterface;
use Triniti\Dam\Exception\InvalidArgumentException;
use Triniti\Dam\Util\MimeTypeUtil;
use Triniti\Schemas\Dam\AssetId;

class AssetUploader
{
    protected GuzzleClientInterface $guzzleClient;
    protected S3Client $s3Client;
    protected string $bucket;

    public function __construct(S3Client $s3Client, string $damBucket, ?GuzzleClientInterface $guzzleClient = null)
    {
        $this->s3Client = $s3Client;
        $this->bucket = $damBucket;
        $this->guzzleClient = $guzzleClient ?: new GuzzleClient();
    }

    public function fromFile(AssetId $assetId, string $filename, array $options = []): void
    {
        $this->uploadToS3($assetId, $filename, $options);
    }

    public function fromUrl(AssetId $assetId, string $url, array $options = []): void
    {
        $filename = tempnam(sys_get_temp_dir(), $assetId->getUuid());
        $response = $this->guzzleClient->request('GET', $url, [
            'sink'    => $filename,
            'timeout' => $options['timeout'] ?? 0,
        ]);

        if (!isset($options['mimeType'])) {
            $options['mimeType'] = trim(explode(';', $response->getHeaderLine('Content-Type'))[0]);
        }

        $bytes = (int)@filesize($filename);
        if ($bytes < 5) {
            throw new InvalidArgumentException('AssetUploader::fromUrl file body is empty.');
        }

        $this->uploadToS3($assetId, $filename, $options);
    }

    protected function uploadToS3(AssetId $assetId, string $filename, array $options = []): void
    {
        $mimeType = $options['mimeType'] ?? MimeTypeUtil::mimeTypeFromExtension($assetId->getExt());
        $version = $options['version'] ?? 'o';
        $quality = $options['quality'] ?? null;

        $this->s3Client->putObject([
            'Bucket'       => $this->bucket,
            'Key'          => $assetId->toFilePath($version, $quality),
            'SourceFile'   => $filename,
            'ContentType'  => $mimeType,
            'ACL'          => 'public-read',
            'CacheControl' => 'max-age=31536000', // 1 year
            'Metadata'     => array_merge($options['metadata'] ?? [], [
                'asset-ref' => $assetId->toNodeRef()->toString(),
            ]),
        ]);

        if (true === (bool)($options['deleteOnComplete'] ?? true)) {
            @unlink($filename);
        }
    }
}
