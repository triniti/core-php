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
        return $this->artifactUrlProvider->getArtifactUrl($id, $type);
    }
}
