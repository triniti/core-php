<?php
declare(strict_types=1);

namespace Triniti\Dam;

use Symfony\Component\HttpKernel\KernelInterface;
use Triniti\Schemas\Dam\AssetId;

class UrlProvider
{
    private static ?self $instance = null;

    /**
     * An array keyed by the asset type with base urls.
     * @example
     * [
     *     'default' => 'http://dam.acme.com/',
     *     'image'   => 'http://images.acme.com/',
     * ]
     *
     * @var string[]
     */
    private array $baseUrls = [];

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
                self::$instance = new static();
            }
        }

        return self::$instance;
    }

    public function __construct(array $baseUrls = [])
    {
        $baseUrls += ['default' => '/'];
        $this->baseUrls = $baseUrls;
    }

    public function getUrl(AssetId $id, ?string $version = 'o', ?string $quality = null): string
    {
        $baseUrl = $this->baseUrls[$id->getType()] ?? $this->baseUrls['default'];
        return "{$baseUrl}{$id->toFilePath($version, $quality)}";
    }
}
