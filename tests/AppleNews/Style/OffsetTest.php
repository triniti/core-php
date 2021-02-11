<?php
declare(strict_types=1);

namespace Triniti\Tests\AppleNews\Style;

use Assert\AssertionFailedException;
use PHPUnit\Framework\TestCase;
use Triniti\AppleNews\Style\Offset;

class OffsetTest extends TestCase
{
    protected Offset $offset;

    public function setUp(): void
    {
        $this->offset = new Offset();
    }

    public function testCreateOffset(): void
    {
        $this->assertInstanceOf('Triniti\AppleNews\Style\Offset', $this->offset);
    }

    public function testSetX(): void
    {
        $this->assertNull($this->offset->getX());

        $this->offset->setX(-45);
        $this->assertEquals(-45, $this->offset->getX());

        $this->offset->setX(20);
        $this->assertEquals(20, $this->offset->getX());
    }

    public function testSetXInvalidUpper(): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->offset->setX(55);
    }

    public function testSetXInvalidLower(): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->offset->setX(-51);
    }

    public function testSetY(): void
    {
        $this->assertNull($this->offset->getY());

        $this->offset->setY(-45);
        $this->assertEquals(-45, $this->offset->getY());

        $this->offset->setY(20);
        $this->assertEquals(20, $this->offset->getY());
    }

    public function testSetYInvalidUpper(): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->offset->setY(55);
    }

    public function testSetYInvalidLower(): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->offset->setY(-51);
    }

    public function testJsonSerialize(): void
    {
        $this->offset->setX(30)->setY(20.5);
        $expectedJson = '{"x":30,"y":20.5}';
        $this->assertJsonStringEqualsJsonString($expectedJson, json_encode($this->offset));
    }

    public function testValidate(): void
    {
        $this->offset->setX(30)->setY(-30);
        try {
            $this->offset->validate();
        } catch (AssertionFailedException $afe) {
            $this->assertTrue(false);
        }

        $this->assertTrue(true, 'no exception should be thrown');
    }

    public function testValidationInvalid(): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->offset->setY(30);
        $this->offset->validate();
    }
}


