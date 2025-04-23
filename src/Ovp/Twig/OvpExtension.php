<?php
declare(strict_types=1);

namespace Triniti\Ovp\Twig;

use Gdbots\Pbj\Message;
use Gdbots\Pbj\WellKnown\NodeRef;
use Triniti\Ovp\ArtifactUrlProvider;
use Triniti\Ovp\Util\OvpUtil;
use Triniti\Schemas\Dam\AssetId;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class OvpExtension extends AbstractExtension
{
    private ArtifactUrlProvider $artifactUrlProvider;

    public function __construct(ArtifactUrlProvider $artifactUrlProvider)
    {
        $this->artifactUrlProvider = $artifactUrlProvider;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('ovp_artifact_url', [OvpUtil::class, 'getUrl']),
        ];
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
