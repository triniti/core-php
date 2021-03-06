<?php
declare(strict_types=1);

namespace Triniti\Tests\Dam\Twig;

use Acme\Schemas\Dam\Node\ImageAssetV1;
use Acme\Schemas\News\Node\ArticleV1;
use PHPUnit\Framework\TestCase;
use Triniti\Dam\Twig\DamExtension;
use Triniti\Dam\UrlProvider;
use Triniti\Schemas\Dam\AssetId;

final class DamExtensionTest extends TestCase
{
    public function test(): void
    {
        $urlProvider = new UrlProvider();
        $extension = new DamExtension($urlProvider);

        $id = AssetId::create('image', 'jpg');
        $asset = ImageAssetV1::fromArray(['_id' => $id]);
        $this->assertSame(null, $extension->getUrl(null));
        $this->assertSame(null, $extension->getUrl(''));
        $this->assertSame(null, $extension->getUrl(ArticleV1::create()));
        $url = $urlProvider->getUrl($id);
        $this->assertSame($url, $extension->getUrl($id));
        $this->assertSame($url, $extension->getUrl($asset->generateNodeRef()));
        $this->assertSame($url, $extension->getUrl($asset));
        $this->assertSame($url, $extension->getUrl((string)$id));
    }
}
