<?php
declare(strict_types=1);

namespace Triniti\Tests\AppleNews\Style;

use Assert\AssertionFailedException;
use PHPUnit\Framework\TestCase;
use Triniti\AppleNews\Style\DropCapStyle;

class DropCapStyleTest extends TestCase
{
    protected DropCapStyle $dropCapStyle;

    public function setUp(): void
    {
        $this->dropCapStyle = new DropCapStyle();
    }

    public function testCreateDropCapStyle(): void
    {
        $this->assertInstanceOf('Triniti\AppleNews\Style\DropCapStyle', $this->dropCapStyle);
    }

    public function testGetSetNumberOfRaisedLines(): void
    {
        $this->assertNull($this->dropCapStyle->getNumberOfRaisedLines());
        $this->dropCapStyle->setNumberOfRaisedLines(2);
        $this->assertEquals(2, $this->dropCapStyle->getNumberOfRaisedLines());
    }

    public function testSetBackgroundColor(): void
    {
        $this->assertNull($this->dropCapStyle->getBackgroundColor());

        $this->dropCapStyle->setBackgroundColor('red');
        $this->assertEquals('red', $this->dropCapStyle->getBackgroundColor());
    }

    public function testSetFontName(): void
    {
        $this->assertNull($this->dropCapStyle->getFontName());

        $this->dropCapStyle->setFontName('a name');
        $this->assertEquals('a name', $this->dropCapStyle->getFontName());
    }

    public function testSetNumberOfCharacters(): void
    {
        $this->assertEquals(1, $this->dropCapStyle->getNumberOfCharacters());

        $this->dropCapStyle->setNumberOfCharacters(4);
        $this->assertEquals(4, $this->dropCapStyle->getNumberOfCharacters());

        $this->dropCapStyle->setNumberOfCharacters();
        $this->assertEquals(1, $this->dropCapStyle->getNumberOfCharacters());
    }

    public function testSetNumberOfCharactersInvalid(): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->dropCapStyle->setNumberOfCharacters(5);
    }

    public function testSetNumberOfLines(): void
    {
        $this->assertNull($this->dropCapStyle->getNumberOfLines());

        $this->dropCapStyle->setNumberOfLines(3);
        $this->assertEquals(3, $this->dropCapStyle->getNumberOfLines());
    }

    public function testSetPadding(): void
    {
        $this->assertEquals(0, $this->dropCapStyle->getPadding());

        $this->dropCapStyle->setPadding(1);
        $this->assertEquals(1, $this->dropCapStyle->getPadding());

        $this->dropCapStyle->setPadding();
        $this->assertEquals(0, $this->dropCapStyle->getPadding());
    }

    public function testSetTextColor(): void
    {
        $this->assertNull($this->dropCapStyle->getTextColor());

        $this->dropCapStyle->setTextColor('#555555');
        $this->assertEquals('#555555', $this->dropCapStyle->getTextColor());
    }

    public function testValidate(): void
    {
        $this->dropCapStyle->setNumberOfLines(5);
        try {
            $this->dropCapStyle->validate();
        } catch (\Exception $e) {
            $this->assertTrue(false);
        }

        $this->assertTrue(true, 'no exception should be thrown');
    }

    public function testValidationInvalid(): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->dropCapStyle->setPadding(3)->setNumberOfRaisedLines(6);

        $this->dropCapStyle->validate();
    }

    public function testJsonSerialize(): void
    {
        $this->dropCapStyle->setNumberOfLines(3);
        $this->dropCapStyle->setPadding(1);
        $expectedJson = '{"numberOfLines":3,"padding":1,"numberOfCharacters":1}';
        $this->assertJsonStringEqualsJsonString($expectedJson, json_encode($this->dropCapStyle));
    }
}
