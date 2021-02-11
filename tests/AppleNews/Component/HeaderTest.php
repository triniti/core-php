<?php

namespace Triniti\Tests\AppleNews\Layout;

use PHPUnit\Framework\TestCase;
use Triniti\AppleNews\Component\Header;

class HeaderTest extends TestCase
{
    protected Header $header;

    public function setUp(): void
    {
        $this->header = new Header();
    }

    public function testCreateHeader(): void
    {
        $this->assertInstanceOf(
            'Triniti\AppleNews\Component\Header',
            $this->header
        );
    }

    public function testJsonSerialize(): void
    {
        $expected = [
            'role' => 'header',
        ];

        $this->assertJsonStringEqualsJsonString(json_encode($expected), json_encode($this->header));
    }
}
