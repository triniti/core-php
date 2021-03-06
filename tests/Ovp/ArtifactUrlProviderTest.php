<?php
declare(strict_types=1);

namespace Triniti\Tests\Ovp;

use PHPUnit\Framework\TestCase;
use Triniti\Dam\UrlProvider as DamUrlProvider;
use Triniti\Ovp\ArtifactUrlProvider;
use Triniti\Schemas\Dam\AssetId;

final class ArtifactUrlProviderTest extends TestCase
{
    private ArtifactUrlProvider $artifactUrlProvider;
    private DamUrlProvider $damUrlProvider;
    private AssetId $assetId;
    private string $basePath;

    public function setUp(): void
    {
        $this->damUrlProvider = new DamUrlProvider();
        $this->artifactUrlProvider = new ArtifactUrlProvider($this->damUrlProvider);
        $this->assetId = AssetId::create('video', 'mxf');
        $this->basePath = explode('.', $this->damUrlProvider->getUrl($this->assetId))[0];
    }

    public function testGetAudio(): void
    {
        $actual = $this->artifactUrlProvider->getAudio($this->assetId);
        $expected = $this->basePath . '.wav';
        $this->assertSame($actual, $expected);
    }

    public function testGetManifest(): void
    {
        $actual = $this->artifactUrlProvider->getManifest($this->assetId);
        $expected = $this->basePath . '.m3u8';
        $this->assertSame($actual, $expected);
    }

    public function testGetTooltipThumbnailSprite(): void
    {
        $actual = $this->artifactUrlProvider->getTooltipThumbnailSprite($this->assetId);
        $expected = $this->basePath . '-tooltip-thumbnail-sprite.jpg';
        $this->assertSame($actual, $expected);
    }

    public function testGetTooltipThumbnailTrack(): void
    {
        $actual = $this->artifactUrlProvider->getTooltipThumbnailTrack($this->assetId);
        $expected = $this->basePath . '-tooltip-thumbnail-track.vtt';
        $this->assertSame($actual, $expected);
    }

    public function testGetTranscription(): void
    {
        $actual = $this->artifactUrlProvider->getTranscription($this->assetId);
        $expected = $this->basePath . '-transcribed.json';
        $this->assertSame($actual, $expected);
    }

    public function testGetVideo(): void
    {
        $actual = $this->artifactUrlProvider->getVideo($this->assetId);
        $expected = $this->basePath . '.mp4';
        $this->assertSame($actual, $expected);
    }

    public function testGetOriginal(): void
    {
        $actual = $this->artifactUrlProvider->getOriginal($this->assetId);
        $expected = $this->basePath . '-original.' . $this->assetId->getExt();
        $this->assertSame($actual, $expected);
    }
}
