<?php
declare(strict_types=1);

namespace Triniti\Ovp\Twig;

use Gdbots\Pbj\Message;
use Gdbots\Pbj\WellKnown\NodeRef;
use Triniti\Ovp\Util\OvpUtil;
use Triniti\Schemas\Dam\AssetId;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;


final class OvpExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('ovp_artifact_url', [OvpUtil::class, 'getUrl']),
        ];
    }
}
