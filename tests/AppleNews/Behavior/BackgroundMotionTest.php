<?php
declare(strict_types=1);

namespace Triniti\Tests\AppleNews\Behavior;

use PHPUnit\Framework\TestCase;
use Triniti\AppleNews\Behavior\BackgroundMotion;

class BackgroundMotionTest extends TestCase
{
    protected BackgroundMotion $backgroundMotion;

    public function setUp(): void
    {
        $this->backgroundMotion = new BackgroundMotion();
    }

    public function testCreateBackgroundMotion(): void
    {
        $this->assertInstanceOf('Triniti\AppleNews\Behavior\BackgroundMotion',
            $this->backgroundMotion);
    }

    public function testJsonSerialize(): void
    {
        $expectedJson = '{"type":"background_motion"}';
        $this->assertJsonStringEqualsJsonString($expectedJson, json_encode($this->backgroundMotion));
    }
}
