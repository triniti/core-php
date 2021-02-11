<?php

namespace Triniti\Tests\AppleNews\Layout;

use PHPUnit\Framework\TestCase;
use Triniti\AppleNews\Component\Body;

class BodyTest extends TestCase
{
    protected Body $body;

    public function setUp(): void
    {
        $this->body = new Body();
    }

    public function testCreateBody(): void
    {
        $this->assertInstanceOf(
            'Triniti\AppleNews\Component\Body',
            $this->body
        );
    }

    public function testJsonSerialize(): void
    {
        $expected = [
            'role' => 'body',
        ];

        $this->assertJsonStringEqualsJsonString(json_encode($expected), json_encode($this->body));
    }
}
