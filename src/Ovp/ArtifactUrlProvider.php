<?php
declare(strict_types=1);

namespace Triniti\Ovp;


use Symfony\Component\HttpKernel\KernelInterface;
use Triniti\Dam\UrlProvider;
use Triniti\Schemas\Dam\AssetId;

class ArtifactUrlProvider
{
    protected UrlProvider $urlProvider;

    private static ?self $instance = null;

    public static function setInstance(self $instance): void
    {
        self::$instance = $instance;
    }

    public static function getInstance(): static
    {
        global $kernel;

        if (null === self::$instance) {
            if ($kernel instanceof KernelInterface) {
                self::$instance = $kernel->getContainer()->get(self::class);
            } else {
                self::$instance = new static(new UrlProvider());
            }
        }

        return self::$instance;
    }

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

    public function getSubtitledManifest(AssetId $id): string
    {
        return $this->getPartial($id) . '-subtitled.m3u8';
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
