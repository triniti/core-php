<?php
declare(strict_types=1);

namespace Triniti\Dam;

use Triniti\Schemas\Dam\AssetId;

class UrlService
{
    protected static ?UrlProvider $urlProvider = null;

    public static function setProvider(UrlProvider $urlProvider): void
    {
        self::$urlProvider = $urlProvider;
    }

    public static function getUrl(AssetId $id, ?string $version = 'o', ?string $quality = null): string
    {
        return self::$urlProvider->getUrl($id, $version, $quality);
    }
}
