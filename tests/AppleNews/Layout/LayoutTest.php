<?php
declare(strict_types=1);

namespace Triniti\Tests\AppleNews\Layout;

use Assert\AssertionFailedException;
use PHPUnit\Framework\TestCase;
use Triniti\AppleNews\Layout\Layout;

class LayoutTest extends TestCase
{
    protected Layout $layout;

    protected function setup(): void
    {
        $this->layout = new Layout();
    }

    public function testCreateLayout(): void
    {
        $this->assertInstanceOf(
            'Triniti\AppleNews\Layout\Layout',
            $this->layout
        );
    }

    public function testSetGutter(): void
    {
        $this->assertNull($this->layout->getGutter());

        $this->layout->setGutter();
        $this->assertEquals(20, $this->layout->getGutter());

        $this->layout->setGutter(30);
        $this->assertEquals(30, $this->layout->getGutter());
    }

    public function testSetGutterValidation(): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage('gutter can not be negative');
        $this->layout->setGutter(-1);
    }

    public function testSetColumns(): void
    {
        $this->layout->setColumns(3);
        $this->assertEquals(3, $this->layout->getColumns());
    }

    public function testSetColumnsValidation(): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage('You need at least 1 columns');
        $this->layout->setColumns(-1);
    }

    public function testSetWidth(): void
    {
        $this->assertNull($this->layout->getWidth());

        $this->layout->setWidth(100);
        $this->assertEquals(100, $this->layout->getWidth());
    }

    public function testSetWidthValidation(): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage('The width cannot be negative or 0');
        $this->layout->setWidth(0);
    }

    public function testSetMargin(): void
    {
        $this->assertNull($this->layout->getMargin());

        $this->layout->setMargin(100);
        $this->assertEquals(100, $this->layout->getMargin());
    }

    public function testValidationValid(): void
    {
        $this->layout->setColumns(5)->setWidth(100)->setMargin(5);
        try {
            $this->layout->validate();
        } catch (\Assert\AssertionFailedException $e) {
            $this->assertTrue(false, 'No AssertionFailedException should be throwed.');
        }

        $this->assertTrue(true);
    }

    public function testValidatoinInvalidNoWidth(): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage('Width can not be null');
        $this->layout->setColumns(5)->setMargin(5);
        $this->layout->validate();
    }

    public function testValidatoinInvalidNoColumns(): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage('Columns can not be null');
        $this->layout->setWidth(1080)->setMargin(5);
        $this->layout->validate();
    }

    public function testValidatoinInvalidWrongMargin(): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage('Margin can not be greater than or equal to the width/2');
        $this->layout->setColumns(5)->setWidth(1024)->setMargin(512);
        $this->layout->validate();
    }

    public function testJsonSerialize(): void
    {
        $expected = [
            'columns' => 7,
            'width'   => 1024,
            'margin'  => 30,
        ];

        $this->layout
            ->setColumns(7)
            ->setWidth(1024)
            ->setMargin(30);
        $this->assertJsonStringEqualsJsonString(json_encode($expected), json_encode($this->layout));

        $expected['gutter'] = 20;
        $this->layout->setGutter(20);
        $this->assertJsonStringEqualsJsonString(json_encode($expected), json_encode($this->layout));
    }
}
