<?php
declare(strict_types=1);

namespace Triniti\Ovp;

use Gdbots\Pbj\Message;
use Gdbots\Pbj\WellKnown\NodeRef;
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

    /**
     * @param AssetId|NodeRef|Message|string $id
     * @param string                         $type
     *
     * @return string|null
     */
    public function getUrl($id, string $type): ?string
    {
        if (empty($id)) {
            return null;
        }

        $assetId = null;
        if ($id instanceof AssetId) {
            $assetId = $id;
        } else if ($id instanceof NodeRef) {
            $assetId = AssetId::fromString($id->getId());
        } else if ($id instanceof Message) {
            $assetId = $id->get('_id');
            if (!$assetId instanceof AssetId) {
                return null;
            }
        } else {
            try {
                $assetId = AssetId::fromString((string)$id);
            } catch (\Throwable $e) {
                return null;
            }
        }

        return match ($type) {
            'audio' => $this->urlProvider->getAudio($assetId),
            'manifest' => $this->urlProvider->getManifest($assetId),
            'original' => $this->urlProvider->getOriginal($assetId),
            'tooltip_thumbnail_sprite' => $this->urlProvider->getTooltipThumbnailSprite($assetId),
            'tooltip_thumbnail_track' => $this->urlProvider->getTooltipThumbnailTrack($assetId),
            'transcription' => $this->urlProvider->getTranscription($assetId),
            'video' => $this->urlProvider->getVideo($assetId),
            default => null,
        };
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
