<?php
declare(strict_types=1);

namespace Triniti\Tests\AppleNews\Behavior;

use PHPUnit\Framework\TestCase;
use Triniti\AppleNews\Behavior\BackgroundParallax;

class BackgroundParallaxTest extends TestCase
{
    protected BackgroundParallax $backgroundParallax;

    public function setUp(): void
    {
        $this->backgroundParallax = new BackgroundParallax();
    }

    public function testCreateBackgroundParallax(): void
    {
        $this->assertInstanceOf('Triniti\AppleNews\Behavior\BackgroundParallax',
            $this->backgroundParallax);
    }

    public function testJsonSerialize(): void
    {
        $expectedJson = '{"type":"background_parallax"}';
        $this->assertJsonStringEqualsJsonString($expectedJson, json_encode($this->backgroundParallax));
    }
}
