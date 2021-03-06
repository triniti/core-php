<?php

namespace Triniti\Tests\AppleNews\Layout;

use PHPUnit\Framework\TestCase;
use Triniti\AppleNews\Component\Title;

class TitleTest extends TestCase
{
    protected Title $title;

    public function setUp(): void
    {
        $this->title = new Title();
    }

    public function testCreateTitle(): void
    {
        $this->assertInstanceOf(
            'Triniti\AppleNews\Component\Title',
            $this->title
        );
    }

    public function testJsonSerialize(): void
    {
        $expected = [
            'role' => 'title',
        ];

        $this->assertJsonStringEqualsJsonString(json_encode($expected), json_encode($this->title));
    }
}
