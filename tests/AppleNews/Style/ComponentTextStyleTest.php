<?php
declare(strict_types=1);

namespace Triniti\Tests\AppleNews\Style;

use Assert\AssertionFailedException;
use PHPUnit\Framework\TestCase;
use Triniti\AppleNews\Style\ComponentTextStyle;
use Triniti\AppleNews\Style\DropCapStyle;
use Triniti\AppleNews\Style\TextDecoration;
use Triniti\AppleNews\Style\TextStyle;

class ComponentTextStyleTest extends TestCase
{
    protected ComponentTextStyle $componentTextStyle;

    public function setUp(): void
    {
        $this->componentTextStyle = new ComponentTextStyle();
    }

    public function testCreateComponentTextStyle(): void
    {
        $this->assertInstanceOf(
            'Triniti\AppleNews\Style\ComponentTextStyle',
            $this->componentTextStyle
        );
    }

    public function testSetDropCapStyle(): void
    {
        $this->assertNull($this->componentTextStyle->getDropCapStyle());

        $dropCapStyle = new DropCapStyle();
        $dropCapStyle->setBackgroundColor('#000')->setTextColor('#FFFFFF')->setNumberOfLines(2);
        $this->componentTextStyle->setDropCapStyle($dropCapStyle);

        $this->assertSame($dropCapStyle, $this->componentTextStyle->getDropCapStyle());
    }

    public function testSetDropCapStyleInvalid(): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->assertNull($this->componentTextStyle->getDropCapStyle());

        $dropCapStyle = new DropCapStyle();
        $dropCapStyle->setBackgroundColor('#000')->setTextColor('#FFFFFF');
        $this->componentTextStyle->setDropCapStyle($dropCapStyle);
    }

    public function testSetFirstLineIndent(): void
    {
        $this->assertNull($this->componentTextStyle->getFirstLineIndent());

        $this->componentTextStyle->setFirstLineIndent(2);
        $this->assertSame(2, $this->componentTextStyle->getFirstLineIndent());

        $this->componentTextStyle->setFirstLineIndent(null);
        $this->assertSame(null, $this->componentTextStyle->getFirstLineIndent());
    }

    public function testSetHangingPunctuation(): void
    {
        $this->assertFalse($this->componentTextStyle->getHangingPunctuation());

        $this->componentTextStyle->setHangingPunctuation();
        $this->assertTrue($this->componentTextStyle->getHangingPunctuation());

        $this->componentTextStyle->setHangingPunctuation(false);
        $this->assertFalse($this->componentTextStyle->getHangingPunctuation());

        $this->componentTextStyle->setHangingPunctuation(true);
        $this->assertTrue($this->componentTextStyle->getHangingPunctuation());
    }

    public function testSetHyphenation(): void
    {
        $this->assertNull($this->componentTextStyle->getHyphenation());

        $this->componentTextStyle->setHyphenation();
        $this->assertTrue($this->componentTextStyle->getHyphenation());

        $this->componentTextStyle->setHyphenation(false);
        $this->assertFalse($this->componentTextStyle->getHyphenation());

        $this->componentTextStyle->setHyphenation(true);
        $this->assertTrue($this->componentTextStyle->getHyphenation());
    }

    public function testSetLineHeight(): void
    {
        $this->assertNull($this->componentTextStyle->getLineHeight());

        $this->componentTextStyle->setLineHeight(12);
        $this->assertSame(12, $this->componentTextStyle->getLineHeight());
    }

    public function testLinkStyle(): void
    {
        $this->assertNull($this->componentTextStyle->getLinkStyle());

        $textStyle = new TextStyle();
        $textStyle->setTextColor('#ABCDEF')->setVerticalAlignment('superscript')->setFontWeight(100);
        $this->componentTextStyle->setLinkStyle($textStyle);

        $this->assertSame($textStyle, $this->componentTextStyle->getLinkStyle());
    }

    public function testSetParagraphSpacingAfter(): void
    {
        $this->assertNull($this->componentTextStyle->getParagraphSpacingAfter());

        $this->componentTextStyle->setParagraphSpacingAfter(15);
        $this->assertSame(15, $this->componentTextStyle->getParagraphSpacingAfter());

        $this->componentTextStyle->setParagraphSpacingAfter(null);
        $this->assertSame(null, $this->componentTextStyle->getParagraphSpacingAfter());
    }

    public function testSetParagraphSpacingBefore(): void
    {
        $this->assertNull($this->componentTextStyle->getParagraphSpacingBefore());

        $this->componentTextStyle->setParagraphSpacingBefore(15);
        $this->assertSame(15, $this->componentTextStyle->getParagraphSpacingBefore());

        $this->componentTextStyle->setParagraphSpacingBefore();
        $this->assertSame(null, $this->componentTextStyle->getParagraphSpacingBefore());
    }

    public function testSetTextAlignment(): void
    {
        $this->assertNull($this->componentTextStyle->getTextAlignment());

        $this->componentTextStyle->setTextAlignment('left');
        $this->assertSame('left', $this->componentTextStyle->getTextAlignment());

        $this->componentTextStyle->setTextAlignment('right');
        $this->assertSame('right', $this->componentTextStyle->getTextAlignment());
    }

    public function testSetTextAlignmentInvalid(): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->componentTextStyle->setTextAlignment('invalid');
    }

    public function testSetTextTransform(): void
    {
        $this->assertNull($this->componentTextStyle->getTextTransform());

        $this->componentTextStyle->setTextTransform('uppercase');
        $this->assertSame('uppercase', $this->componentTextStyle->getTextTransform());

        $this->componentTextStyle->setTextTransform('lowercase');
        $this->assertSame('lowercase', $this->componentTextStyle->getTextTransform());
    }

    public function testSetTextTransformInvalid(): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->componentTextStyle->setTextTransform('invalid');
    }

    public function testJsonSerialize(): void
    {
        $textDecoration = new TextDecoration();
        $textDecoration->setColor('#2a6496');

        $linkStyle = new TextStyle();
        $linkStyle->setUnderline($textDecoration);

        $expected = [
            'fontName'               => 'GillSans-Bold',
            'fontSize'               => 12,
            'paragraphSpacingBefore' => 20,
            'paragraphSpacingAfter'  => 20,
            'textColor'              => '#000000',
            'textAlignment'          => 'right',
            'lineHeight'             => 14,
            'firstLineIndent'        => 20,
            'linkStyle'              => $linkStyle,
            'hangingPunctuation'     => false,
        ];

        $this->componentTextStyle
            ->setParagraphSpacingBefore(20)
            ->setParagraphSpacingAfter(20)
            ->setTextAlignment('right')
            ->setLineHeight(14)
            ->setFirstLineIndent(20)
            ->setLinkStyle($linkStyle)
            ->setTextColor('#000000')
            ->setFontSize(12)
            ->setFontName('GillSans-Bold');

        $this->assertJsonStringEqualsJsonString(json_encode($expected), json_encode($this->componentTextStyle));
    }
}

