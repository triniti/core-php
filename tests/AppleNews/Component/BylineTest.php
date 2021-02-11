<?php

namespace Triniti\Tests\AppleNews\Layout;

use PHPUnit\Framework\TestCase;
use Triniti\AppleNews\Component\Byline;

class BylineTest extends TestCase
{
    protected Byline $byline;

    public function setUp(): void
    {
        $this->byline = new Byline();
    }

    public function testCreateByline(): void
    {
        $this->assertInstanceOf(
            'Triniti\AppleNews\Component\Byline',
            $this->byline
        );
    }

    public function testJsonSerialize(): void
    {
        $expected = [
            'role' => 'byline',
        ];

        $this->assertJsonStringEqualsJsonString(json_encode($expected), json_encode($this->byline));
    }
}
