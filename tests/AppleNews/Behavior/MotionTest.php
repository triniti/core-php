<?php
declare(strict_types=1);

namespace Triniti\Tests\AppleNews\Behavior;

use PHPUnit\Framework\TestCase;
use Triniti\AppleNews\Behavior\Motion;

class MotionTest extends TestCase
{
    protected Motion $motion;

    public function setUp(): void
    {
        $this->motion = new Motion();
    }

    public function testCreateMotion(): void
    {
        $this->assertInstanceOf('Triniti\AppleNews\Behavior\Motion', $this->motion);
    }

    public function testJsonSerialize(): void
    {
        $expectedJson = '{"type":"motion"}';
        $this->assertJsonStringEqualsJsonString($expectedJson, json_encode($this->motion));
    }
}
