<?php
declare(strict_types=1);

namespace Triniti\Tests\AppleNews\Style;

use Assert\AssertionFailedException;
use PHPUnit\Framework\TestCase;
use Triniti\AppleNews\Style\ListItemStyle;

class ListItemStyleTest extends TestCase
{
    protected ListItemStyle $listItemStyle;

    public function setUp(): void
    {
        $this->listItemStyle = new ListItemStyle();
    }

    public function testCreateListItemStyle(): void
    {
        $this->assertInstanceOf('Triniti\AppleNews\Style\ListItemStyle', $this->listItemStyle);
    }

    public function testSetType(): void
    {
        $this->assertEquals('bullet', $this->listItemStyle->getType());

        $this->listItemStyle->setType('decimal');
        $this->assertSame('decimal', $this->listItemStyle->getType());

        $this->listItemStyle->setType();
        $this->assertSame('bullet', $this->listItemStyle->getType());
    }

    public function testSetTypeInvalid(): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->listItemStyle->setType('invalid');
    }

    public function testSetCharacter(): void
    {
        $this->assertNull($this->listItemStyle->getCharacter());

        $this->listItemStyle->setCharacter('v');
        $this->assertEquals('v', $this->listItemStyle->getCharacter());
    }

    public function testSetCharacterInvalid(): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage('Only a single character is supported.');
        $this->listItemStyle->setCharacter('ab');
    }

    public function testValidation(): void
    {
        try {
            $this->listItemStyle->setType('lower_roman')->validate();
        } catch (AssertionFailedException $afe) {
            $this->assertTrue(false);
        }

        try {
            $this->listItemStyle->setType('character')->setCharacter('v')->validate();
        } catch (AssertionFailedException $afe) {
            $this->assertTrue(false);
        }

        $this->assertTrue(true, 'no exception should be thrown');
    }

    public function testValidateionWrongCharacter(): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage('A character should be provided when type is "character"');
        $this->listItemStyle->setType('character')->validate();
    }

    public function testJsonSerialize(): void
    {
        $expected = [
            'type' => 'lower_roman',
        ];

        $this->listItemStyle->setType('lower_roman');
        $this->assertJsonStringEqualsJsonString(json_encode($expected), json_encode($this->listItemStyle));

        $expected['type'] = 'character';
        $expected['character'] = 'v';
        $this->listItemStyle->setType('character')->setCharacter('v');
        $this->assertJsonStringEqualsJsonString(json_encode($expected), json_encode($this->listItemStyle));
    }
}


