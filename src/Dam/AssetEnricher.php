<?php
declare(strict_types=1);

namespace Triniti\Dam;

use Aws\S3\S3Client;
use Brick\Math\BigInteger;
use Gdbots\Pbjx\DependencyInjection\PbjxEnricher;
use Gdbots\Pbjx\Event\PbjxEvent;
use Gdbots\Pbjx\EventSubscriber;
use GuzzleHttp\RetryMiddleware;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Triniti\Schemas\Dam\AssetId;

class AssetEnricher implements EventSubscriber, PbjxEnricher
{
    protected const MAX_RETRIES = 3;

    protected LoggerInterface $logger;
    protected S3Client $s3Client;
    protected string $bucket;

    public static function getSubscribedEvents(): array
    {
        return [
            'triniti:dam:mixin:asset.enrich' => 'enrichWithS3Object',
        ];
    }

    public function __construct(S3Client $s3Client, string $damBucket, ?LoggerInterface $logger = null)
    {
        $this->s3Client = $s3Client;
        $this->bucket = $damBucket;
        $this->logger = $logger ?: new NullLogger();
    }

    /**
     * Since assets are uploaded directly to S3 from the client we
     * don't get all of the metadata about the object itself during
     * asset (the node) creation/update/etc.
     *
     * If the "file_etag" is not set then we'll attempt to get it from
     * Amazon using S3 HeadObject
     *
     * @link https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-s3-2006-03-01.html#headobject
     *
     * @param PbjxEvent $pbjxEvent
     */
    public function enrichWithS3Object(PbjxEvent $pbjxEvent): void
    {
        $asset = $pbjxEvent->getMessage();

        if ($pbjxEvent->hasParentEvent()) {
            $parentEvent = $pbjxEvent->getParentEvent()->getMessage();
            $schema = $parentEvent::schema();
            if (!$schema->hasMixin('gdbots:pbjx:mixin:event')) {
                return;
            }
        }

        if ($asset->has('file_etag')) {
            // nothing to do here
            return;
        }

        /** @var AssetId $assetId */
        $assetId = $asset->get('_id');
        $key = $this->getKey($assetId);
        $retries = 0;

        while (true) {
            try {
                $result = $this->s3Client->headObject([
                    'Bucket' => $this->bucket,
                    'Key'    => $key,
                    '@http'  => [
                        'delay' => RetryMiddleware::exponentialDelay($retries),
                    ],
                ]);
                $asset
                    ->set('file_etag', trim((string)$result['ETag'], '"') ?: null)
                    ->set('file_size', BigInteger::fromBase((string)((int)$result['ContentLength']), 10))
                    ->set('mime_type', (string)$result['ContentType'] ?: 'application/octet-stream');
                return;
            } catch (\Throwable $e) {
                if ($retries < self::MAX_RETRIES) {
                    ++$retries;
                    continue;
                }

                /*
                 * we don't throw the exception here because it's possible the file
                 * wasn't uploaded yet as node creation can sometimes happen
                 * before file is done uploading.  In those cases there will likely
                 * be an update-asset command following the upload.
                 *
                 * We do however want to log it for now.
                 */
                $this->logger->error(
                    'Unable to head S3 object [{bucket}/{key}] after [{retries}] retries during [{parent_event}].',
                    [
                        'exception'    => $e,
                        'bucket'       => $this->bucket,
                        'key'          => $key,
                        'retries'      => $retries,
                        'asset'        => $asset->toArray(),
                        'parent_event' => $pbjxEvent->isRootEvent()
                            ? null
                            : $pbjxEvent->getParentEvent()->getMessage()->schema()->getId()->toString(),
                    ]
                );
                return;
            }
        }
    }

    protected function getKey(AssetId $assetId): string
    {
        return $assetId->toFilePath('o');
    }
}
