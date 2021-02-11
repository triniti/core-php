<?php
declare(strict_types=1);

namespace Triniti\Dam;

use Aws\S3\S3Client;
use Gdbots\Pbj\Message;
use Gdbots\Pbjx\DependencyInjection\PbjxProjector;
use Gdbots\Pbjx\EventSubscriberTrait;
use Gdbots\Pbjx\Pbjx;

class S3AssetProjector implements PbjxProjector
{
    use EventSubscriberTrait;

    protected S3Client $s3Client;
    protected string $bucket;

    public function __construct(S3Client $s3Client, string $damBucket)
    {
        $this->s3Client = $s3Client;
        $this->bucket = $damBucket;
    }

    public function onAssetCreated(Message $event, Pbjx $pbjx): void
    {
        // todo: update asset metadata or tagging in s3?
    }

    public function onAssetDeleted(Message $event, Pbjx $pbjx): void
    {
        // todo: delete asset in s3?
    }

    public function onAssetExpired(Message $event, Pbjx $pbjx): void
    {
        // todo: mark asset in s3 as expired, replace it with something else?
    }

    public function onAssetUpdated(Message $event, Pbjx $pbjx): void
    {
        // todo: update metadata or tagging?
    }
}
