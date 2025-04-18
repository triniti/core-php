<?php
declare(strict_types=1);

namespace Triniti\Ovp\Util;

use Triniti\Ovp\ArtifactUrlProvider;
use Triniti\Schemas\Dam\AssetId;
use Gdbots\Pbj\WellKnown\NodeRef;
use Gdbots\Pbj\Message;


final readonly class OvpUtil {
    public function __construct(private readonly ArtifactUrlProvider $artifactUrlProvider)
    {
    }

    public function getArtifactUrl($id, string $type): ?string
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
            'audio' => $this->artifactUrlProvider->getAudio($assetId),
            'manifest' => $this->artifactUrlProvider->getManifest($assetId),
            'original' => $this->artifactUrlProvider->getOriginal($assetId),
            'tooltip_thumbnail_sprite' => $this->artifactUrlProvider->getTooltipThumbnailSprite($assetId),
            'tooltip_thumbnail_track' => $this->artifactUrlProvider->getTooltipThumbnailTrack($assetId),
            'transcription' => $this->artifactUrlProvider->getTranscription($assetId),
            'video' => $this->artifactUrlProvider->getVideo($assetId),
            default => null,
        };
    }
}
