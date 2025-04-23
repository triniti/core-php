<?php
declare(strict_types=1);

namespace Triniti\Ovp\Twig;

use Triniti\Ovp\ArtifactUrlProvider;
use Triniti\Ovp\Util\OvpUtil;
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
            new TwigFunction('ovp_artifact_url', [ArtifactUrlProvider::class, 'getArtifactUrl']),
        ];
    }
}
