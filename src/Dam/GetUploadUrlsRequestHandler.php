<?php
declare(strict_types=1);

namespace Triniti\Dam;

use Aws\S3\S3Client;
use Gdbots\Pbj\Message;
use Gdbots\Pbj\MessageResolver;
use Gdbots\Pbj\WellKnown\NodeRef;
use Gdbots\Pbjx\Pbjx;
use Gdbots\Pbjx\RequestHandler;
use Triniti\Dam\Exception\AssetTypeNotSupported;
use Triniti\Dam\Exception\InvalidArgumentException;
use Triniti\Dam\Util\MimeTypeUtil;
use Triniti\Schemas\Dam\AssetId;

final class GetUploadUrlsRequestHandler implements RequestHandler
{
    private S3Client $s3Client;
    private string $bucket;

    public static function handlesCuries(): array
    {
        // deprecated mixins, will be removed in 3.x
        $curies = MessageResolver::findAllUsingMixin('triniti:dam:mixin:get-upload-urls-request:v1', false);
        $curies[] = 'triniti:dam:request:get-upload-urls-request';
        return $curies;
    }

    public function __construct(S3Client $s3Client, string $damBucket)
    {
        $this->s3Client = $s3Client;
        $this->bucket = $damBucket;
    }

    public function handleRequest(Message $request, Pbjx $pbjx): Message
    {
        $response = MessageResolver::resolveCurie('*:dam:request:get-upload-urls-response:v1')::create();

        if (!$request->has('files')) {
            return $response;
        }

        if ($request->has('asset_id') && 1 !== count($request->get('files'))) {
            throw new InvalidArgumentException('Variant requires a single file.');
        }

        $version = $request->get('version', 'o');
        $quality = $request->get('quality');

        if ($request->has('asset_id') && 'o' === $version) {
            throw new InvalidArgumentException('Variant cannot overwrite the original file.');
        }

        foreach ($request->get('files') as $filename) {
            $filenameHash = md5($filename);
            try {
                $extension = pathinfo($filename, PATHINFO_EXTENSION);

                if (empty($extension)) {
                    throw new AssetTypeNotSupported("The file '{$filename}' has no extension.");
                }

                $mimeType = MimeTypeUtil::mimeTypeFromFilename($filename);
                $type = MimeTypeUtil::assetTypeFromMimeType($mimeType);
                $date = $request->get('occurred_at')->toDateTime();
                $assetId = $request->get('asset_id', AssetId::create($type, $extension, $date));

                if ($assetId->getType() !== $type) {
                    throw new AssetTypeNotSupported("The file '{$filename}' type does not match the provided asset id '{$assetId}'");
                }

                $command = $this->s3Client->getCommand('PutObject', [
                    'Bucket'       => $this->bucket,
                    'Key'          => $assetId->toFilePath($version, $quality),
                    'ACL'          => 'public-read',
                    'CacheControl' => 'max-age=31536000', // 1 year
                    'Metadata'     => [
                        'asset-ref' => $this->assetIdToNodeRef($assetId)->toString(),
                    ],
                ]);

                $s3Request = $this->s3Client->createPresignedRequest($command, '+20 minutes');
                $presignedUrl = (string)$s3Request->getUri();

                $response->addToMap('asset_ids', $filenameHash, $assetId);
                $response->addToMap('s3_presigned_urls', $filenameHash, $presignedUrl);
            } catch (\Throwable $e) {
                /*
                 * todo: Add an errors map that contains the filename hash as the key and any
                 * specifics about the error that we want to reveal to the client.
                 */
            }
        }

        return $response;
    }

    protected function assetIdToNodeRef(AssetId $assetId): NodeRef
    {
        $vendor = MessageResolver::getDefaultVendor();
        return NodeRef::fromString("{$vendor}:{$assetId->getType()}-asset:{$assetId}");
    }
}
