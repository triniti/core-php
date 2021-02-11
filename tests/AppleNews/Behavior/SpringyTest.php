<?php
declare(strict_types=1);

namespace Triniti\Tests\AppleNews\Behavior;

use PHPUnit\Framework\TestCase;
use Triniti\AppleNews\Behavior\Springy;

class SpringyTest extends TestCase
{
    protected Springy $springy;

    public function setUp(): void
    {
        $this->springy = new Springy();
    }

    public function testCreateSpringy(): void
    {
        $this->assertInstanceOf('Triniti\AppleNews\Behavior\Springy', $this->springy);
    }

    public function testJsonSerialize(): void
    {
        $expectedJson = '{"type":"springy"}';
        $this->assertJsonStringEqualsJsonString($expectedJson, json_encode($this->springy));
    }
}
