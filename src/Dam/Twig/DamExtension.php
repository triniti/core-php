<?php
declare(strict_types=1);

namespace Triniti\Dam\Twig;

use Gdbots\Pbj\Message;
use Gdbots\Pbj\WellKnown\NodeRef;
use Triniti\Dam\UrlProvider;
use Triniti\Schemas\Dam\AssetId;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class DamExtension extends AbstractExtension
{
    private UrlProvider $urlProvider;

    public function __construct(UrlProvider $urlProvider)
    {
        $this->urlProvider = $urlProvider;
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('dam_url', [$this, 'getUrl']),
        ];
    }

    /**
     * @param AssetId|NodeRef|Message|string $id
     * @param string                       $version
     * @param string                       $quality
     *
     * @return string|null
     */
    public function getUrl($id, ?string $version = 'o', ?string $quality = null): ?string
    {
        if (empty($id)) {
            return null;
        }

        if ($id instanceof AssetId) {
            return $this->urlProvider->getUrl($id, $version, $quality);
        }

        if ($id instanceof NodeRef) {
            return $this->urlProvider->getUrl(AssetId::fromString($id->getId()), $version, $quality);
        }

        if ($id instanceof Message && $id::schema()->hasMixin('triniti:dam:mixin:asset')) {
            return $this->urlProvider->getUrl($id->get('_id'), $version, $quality);
        }

        try {
            return $this->urlProvider->getUrl(AssetId::fromString((string)$id), $version, $quality);
        } catch (\Throwable $e) {
            return null;
        }
    }
}
