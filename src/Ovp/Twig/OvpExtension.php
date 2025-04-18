<?php
declare(strict_types=1);

namespace Triniti\Ovp\Twig;

use Triniti\Ovp\Util\OvpUtil;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;


final class OvpExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('ovp_artifact_url', [OvpUtil::class, 'getArtifactUrl']),
        ];
    }
}
