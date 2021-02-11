<?php
declare(strict_types=1);

namespace Triniti\Dam;

use Triniti\Schemas\Dam\AssetId;

class UrlProvider
{
    /**
     * An array keyed by the asset type with base urls.
     * @example
     * [
     *     'default' => 'http://dam.acme.com/',
     *     'image'   => 'http://images.acme.com/',
     * ]
     *
     * @var string[]
     */
    private $baseUrls = [];

    /**
     * @param array $baseUrls
     */
    public function __construct(array $baseUrls = [])
    {
        $baseUrls += ['default' => '/'];
        $this->baseUrls = $baseUrls;
    }

    /**
     * @param AssetId $id
     * @param string  $version
     * @param string  $quality
     *
     * @return string
     */
    public function getUrl(AssetId $id, ?string $version = 'o', ?string $quality = null): string
    {
        $baseUrl = $this->baseUrls[$id->getType()] ?? $this->baseUrls['default'];
        return "{$baseUrl}{$id->toFilePath($version, $quality)}";
    }
}
