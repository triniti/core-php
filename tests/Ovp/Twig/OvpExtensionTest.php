<?php
declare(strict_types=1);

namespace Triniti\Tests\Ovp\Twig;

use Acme\Schemas\Dam\Node\ImageAssetV1;
use Acme\Schemas\Dam\Node\VideoAssetV1;
use Acme\Schemas\News\Node\ArticleV1;
use Gdbots\Pbj\Util\StringUtil;
use PHPUnit\Framework\TestCase;
use Triniti\Dam\UrlProvider;
use Triniti\Ovp\ArtifactUrlProvider;
use Triniti\Ovp\Twig\OvpExtension;
use Triniti\Schemas\Dam\AssetId;

final class OvpExtensionTest extends TestCase
{
    public function test(): void
    {
        $artifactUrlProvider = new ArtifactUrlProvider(new UrlProvider());
        $extension = new OvpExtension($artifactUrlProvider);

        $id = AssetId::create('video', 'mxf');
        $asset = VideoAssetV1::fromArray(['_id' => $id]);
        $this->assertSame(null, $extension->getUrl(null, 'video'));
        $this->assertSame(null, $extension->getUrl('', 'video'));
        $this->assertSame(null, $extension->getUrl(ArticleV1::create(), 'video'));
        $this->assertSame(null, $extension->getUrl(ImageAssetV1::create(), 'video'));

        foreach (['audio', 'manifest', 'original', 'tooltip_thumbnail_sprite', 'tooltip_thumbnail_track', 'transcription', 'video'] as $type) {
            $methodName = 'get' . StringUtil::toCamelFromSnake($type);
            $artifactUrl = $artifactUrlProvider->$methodName($id);
            $this->assertSame($artifactUrl, $extension->getUrl($id, $type));
            $this->assertSame($artifactUrl, $extension->getUrl($asset->generateNodeRef(), $type));
            $this->assertSame($artifactUrl, $extension->getUrl($asset, $type));
            $this->assertSame($artifactUrl, $extension->getUrl((string)$id, $type));
        }
    }
}
