<?php
declare(strict_types=1);

namespace Triniti\Tests\AppleNews\Scene;

use PHPUnit\Framework\TestCase;
use Triniti\AppleNews\Scene\ParallaxScaleHeader;

class ParallaxScaleHeaderTest extends TestCase
{
    protected ParallaxScaleHeader $parallaxScaleHeader;

    public function setUp(): void
    {
        $this->parallaxScaleHeader = new ParallaxScaleHeader();
    }

    public function testCreateParallaxScaleHeader(): void
    {
        $this->assertInstanceOf(
            'Triniti\AppleNews\Scene\ParallaxScaleHeader',
            $this->parallaxScaleHeader
        );
    }

    public function testJsonSerialize(): void
    {
        $expectedJson = '{"type":"parallax_scale"}';
        $this->assertJsonStringEqualsJsonString($expectedJson, json_encode($this->parallaxScaleHeader));
    }
}
