<?php

namespace Triniti\Tests\AppleNews\Layout;

use PHPUnit\Framework\TestCase;
use Triniti\AppleNews\Component\Aside;

class AsideTest extends TestCase
{
    protected Aside $aside;

    public function setUp(): void
    {
        $this->aside = new Aside();
    }

    public function testCreateAside(): void
    {
        $this->assertInstanceOf(
            'Triniti\AppleNews\Component\Aside',
            $this->aside
        );
    }

    public function testJsonSerialize(): void
    {
        $expected = [
            'role' => 'aside',
        ];

        $this->assertJsonStringEqualsJsonString(json_encode($expected), json_encode($this->aside));
    }
}
