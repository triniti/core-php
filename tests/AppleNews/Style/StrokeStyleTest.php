<?php
declare(strict_types=1);

namespace Triniti\Tests\AppleNews\Style;

use Assert\AssertionFailedException;
use PHPUnit\Framework\TestCase;
use Triniti\AppleNews\Style\StrokeStyle;

class StrokeStyleTest extends TestCase
{
    protected StrokeStyle $strokeStyle;

    public function setUp(): void
    {
        $this->strokeStyle = new StrokeStyle();
    }

    public function testCreateStrokeStyle(): void
    {
        $this->assertInstanceOf('Triniti\AppleNews\Style\StrokeStyle', $this->strokeStyle);
        $this->assertSame(1, $this->strokeStyle->getWidth());
        $this->assertSame('solid', $this->strokeStyle->getStyle());
        $this->assertNull($this->strokeStyle->getColor());
    }

    public function testSetWidth(): void
    {
        $this->strokeStyle->setWidth(2);
        $this->assertSame(2, $this->strokeStyle->getWidth());

        $this->strokeStyle->setWidth();
        $this->assertSame(1, $this->strokeStyle->getWidth(), 'should set width to default value if the value is omitted');
    }

    public function testSetColor(): void
    {
        $this->strokeStyle->setColor('red');
        $this->assertSame('red', $this->strokeStyle->getColor());

        $this->strokeStyle->setColor(null);
        $this->assertNull($this->strokeStyle->getColor());
    }

    public function testSetStyle(): void
    {
        $this->strokeStyle->setStyle('dotted');
        $this->assertEquals('dotted', $this->strokeStyle->getStyle());

        $this->strokeStyle->setStyle('solid');
        $this->assertEquals('solid', $this->strokeStyle->getStyle());

        $this->strokeStyle->setStyle('dashed');
        $this->assertEquals('dashed', $this->strokeStyle->getStyle());
    }

    public function testSetStyleInvalid(): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->strokeStyle->setStyle('invalid');
    }

    public function testJsonSerialize(): void
    {
        $this->strokeStyle->setColor('red');
        $this->strokeStyle->setStyle('dotted');
        $expected = [
            'color' => 'red',
            'width' => 1,
            'style' => 'dotted',
        ];
        $this->assertJsonStringEqualsJsonString(json_encode($expected), json_encode($this->strokeStyle));
    }

}



