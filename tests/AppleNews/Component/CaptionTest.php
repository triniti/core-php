<?php

namespace Triniti\Tests\AppleNews\Layout;

use PHPUnit\Framework\TestCase;
use Triniti\AppleNews\Component\Caption;

class CaptionTest extends TestCase
{
    protected Caption $caption;

    public function setup(): void
    {
        $this->caption = new Caption();
    }

    public function testCreateCaption(): void
    {
        $this->assertInstanceOf(
            'Triniti\AppleNews\Component\Caption',
            $this->caption
        );
    }

    public function testJsonSerialize(): void
    {
        $expected = [
            'role' => 'caption',
        ];

        $this->assertJsonStringEqualsJsonString(json_encode($expected), json_encode($this->caption));
    }
}
