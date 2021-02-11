<?php
declare(strict_types=1);

namespace Triniti\Tests\AppleNews\Style;

use Assert\AssertionFailedException;
use PHPUnit\Framework\TestCase;
use Triniti\AppleNews\Style\InlineTextStyle;
use Triniti\AppleNews\Style\TextStyle;

class InlineTextStyleTest extends TestCase
{
    protected InlineTextStyle $inlineTextStyle;

    public function setUp(): void
    {
        $this->inlineTextStyle = new InlineTextStyle();
    }

    public function testCreateInlineTextStyle(): void
    {
        $this->assertInstanceOf('Triniti\AppleNews\Style\InlineTextStyle', $this->inlineTextStyle);
    }

    public function testGetSetRangeLength(): void
    {
        $this->assertNull($this->inlineTextStyle->getRangeLength());
        $this->inlineTextStyle->setRangeLength(2);
        $this->assertEquals(2, $this->inlineTextStyle->getRangeLength());
    }

    public function testGetSetRangeStart(): void
    {
        $this->assertNull($this->inlineTextStyle->getRangeStart());
        $this->inlineTextStyle->setRangeStart(2);
        $this->assertEquals(2, $this->inlineTextStyle->getRangeStart());
    }

    public function testValidation(): void
    {
        $textStyle = new TextStyle();
        $textStyle->setTextColor('#FF0000')->setBackgroundColor('#000');
        $this->inlineTextStyle
            ->setTextStyle($textStyle)
            ->setRangeLength(9)
            ->setRangeStart(17);

        try {
            $this->inlineTextStyle->validate();
        } catch (\Exception $e) {
            $this->assertTrue(false);
        }

        $this->assertTrue(true, 'no exception should be thrown');
    }

    public function testValidationInvalid(): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->inlineTextStyle
            ->setRangeLength(9)
            ->setRangeStart(17);

        $this->inlineTextStyle->validate();
    }

    public function testJsonSerialize(): void
    {
        $textStyle = new TextStyle();
        $textStyle->setTextColor('#FF0000')
            ->setBackgroundColor('#000');

        $this->inlineTextStyle
            ->setTextStyle($textStyle)
            ->setRangeLength(9)
            ->setRangeStart(17);

        $expected = [
            'rangeStart'  => 17,
            'rangeLength' => 9,
            'textStyle'   => [
                'textColor'       => '#FF0000',
                'backgroundColor' => '#000',
            ],
        ];
        $this->assertJsonStringEqualsJsonString(json_encode($expected), json_encode($this->inlineTextStyle));
    }
}


