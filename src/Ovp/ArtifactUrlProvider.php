<?php
declare(strict_types=1);

namespace Triniti\Ovp;

use Triniti\Dam\UrlProvider;
use Triniti\Schemas\Dam\AssetId;

class ArtifactUrlProvider
{
    protected UrlProvider $urlProvider;

    public function __construct(UrlProvider $urlProvider)
    {
        $this->urlProvider = $urlProvider;
    }

    public function getAudio(AssetId $id): string
    {
        return $this->getPartial($id) . '.wav';
    }

    public function getManifest(AssetId $id): string
    {
        return $this->getPartial($id) . '.m3u8';
    }

    public function getOriginal(AssetId $id): string
    {
        return $this->getPartial($id) . '-original.' . $id->getExt();
    }

    public function getTooltipThumbnailSprite(AssetId $id): string
    {
        return $this->getPartial($id) . '-tooltip-thumbnail-sprite.jpg';
    }

    public function getTooltipThumbnailTrack(AssetId $id): string
    {
        return $this->getPartial($id) . '-tooltip-thumbnail-track.vtt';
    }

    public function getTranscription(AssetId $id): string
    {
        return $this->getPartial($id) . '-transcribed.json';
    }

    public function getVideo(AssetId $id): string
    {
        return $this->getPartial($id) . '.mp4';
    }

    protected function getPartial(AssetId $id): string
    {
        $pathInfo = pathinfo($this->urlProvider->getUrl($id));
        return $pathInfo['dirname'] . '/' . $pathInfo['filename'];
    }
}
