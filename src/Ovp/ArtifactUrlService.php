<?php
declare(strict_types=1);

namespace Triniti\Ovp;

use Triniti\Schemas\Dam\AssetId;

class ArtifactUrlService
{
    protected static ?ArtifactUrlProvider $artifactUrlProvider = null;

    public static function setProvider(ArtifactUrlProvider $artifactUrlProvider): void
    {
        self::$artifactUrlProvider = $artifactUrlProvider;
    }

    public static function getAudio(AssetId $id): string
    {
        return self::$artifactUrlProvider->getAudio($id);
    }

    public static function getManifest(AssetId $id): string
    {
        return self::$artifactUrlProvider->getManifest($id);
    }

    public static function getOriginal(AssetId $id): string
    {
        return self::$artifactUrlProvider->getOriginal($id);
    }

    public static function getTooltipThumbnailSprite(AssetId $id): string
    {
        return self::$artifactUrlProvider->getTooltipThumbnailSprite($id);
    }

    public static function getTranscription(AssetId $id): string
    {
        return self::$artifactUrlProvider->getTranscription($id);
    }

    public static function getVideo(AssetId $id): string
    {
        return self::$artifactUrlProvider->getVideo($id);
    }
}
