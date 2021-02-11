<?php

namespace Triniti\Tests\AppleNews\Layout;

use PHPUnit\Framework\TestCase;
use Triniti\AppleNews\Component\Divider;
use Triniti\AppleNews\Style\StrokeStyle;

class DividerTest extends TestCase
{
    protected Divider $divider;

    public function setUp(): void
    {
        $this->divider = new Divider();
    }

    public function testCreateDivider(): void
    {
        $this->assertInstanceOf(
            'Triniti\AppleNews\Component\Divider',
            $this->divider
        );
    }

    public function testGetSetStroke(): void
    {
        $stroke = new StrokeStyle();

        $this->divider->setStroke($stroke);
        $this->assertEquals($stroke, $this->divider->getStroke());

        $this->divider->setStroke();
        $this->assertNull($this->divider->getStroke());
    }

    public function testJsonSerialize(): void
    {
        $expected = [
            'role'   => 'divider',
            'stroke' => new StrokeStyle(),
        ];

        $this->divider->setStroke(new StrokeStyle());

        $this->assertJsonStringEqualsJsonString(json_encode($expected), json_encode($this->divider));
    }
}
