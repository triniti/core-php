<?php
declare(strict_types=1);

namespace Triniti\Tests\AppleNews\Style;

use Assert\AssertionFailedException;
use PHPUnit\Framework\TestCase;
use Triniti\AppleNews\Link\Link;
use Triniti\AppleNews\Style\ComponentTextStyle;
use Triniti\AppleNews\Style\FormattedText;
use Triniti\AppleNews\Style\InlineTextStyle;

class FormattedTextTest extends TestCase
{
    protected FormattedText $formattedText;

    protected function setup(): void
    {
        $this->formattedText = new FormattedText();
    }

    public function testSetTextStyle(): void
    {
        $this->assertNull($this->formattedText->getTextStyle());

        $this->formattedText->setTextStyle('style-string');
        $this->assertSame('style-string', $this->formattedText->getTextStyle());

        $textStyle = new ComponentTextStyle();
        $textStyle
            ->setParagraphSpacingBefore(20)
            ->setParagraphSpacingAfter(20)
            ->setTextAlignment('right')
            ->setLineHeight(14)
            ->setFirstLineIndent(20)
            ->setTextColor('#000000')
            ->setFontSize(12)
            ->setFontName('GillSans-Bold');
        $this->formattedText->setTextStyle($textStyle);
        $this->assertSame($textStyle, $this->formattedText->getTextStyle());
    }

    public function testSetFormat(): void
    {
        $this->assertNull($this->formattedText->getFormat());

        $this->formattedText->setFormat('html');
        $this->assertSame('html', $this->formattedText->getFormat());

        $this->formattedText->setFormat('none');
        $this->assertSame('none', $this->formattedText->getFormat());
    }

    public function testSetFormatInvalid(): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->formattedText->setFormat('invalid');
    }

    public function testSetText(): void
    {
        $this->assertNull($this->formattedText->getText());

        $this->formattedText->setText('hello world');
        $this->assertSame('hello world', $this->formattedText->getText());
    }

    public function testAddAddition(): void
    {
        $this->assertEmpty($this->formattedText->getAdditions());

        $addition = new Link();
        $addition->setURL('http://www.example.com')->setRangeStart(0)->setRangeLength(10);

        $this->formattedText->addAddition($addition);
        $this->assertSame([$addition], $this->formattedText->getAdditions());
    }

    public function testAddAdditionInvalid(): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->assertEmpty($this->formattedText->getAdditions());

        $addition = new Link();
        $addition->setRangeStart(0)->setRangeLength(10);

        $this->formattedText->addAddition($addition);
    }

    public function testAddAdditions(): void
    {
        $this->assertEmpty($this->formattedText->getAdditions());

        $addition = new Link();
        $addition->setURL('http://www.example.com')->setRangeStart(0)->setRangeLength(10);
        $this->formattedText->addAddition($addition);

        $addition1 = new Link();
        $addition1->setURL('http://www.example1.com')->setRangeStart(1)->setRangeLength(11);

        $addition2 = new Link();
        $addition2->setURL('http://www.example2.com')->setRangeStart(2)->setRangeLength(12);

        $additions = [$addition1, $addition2];

        $this->formattedText->addAdditions($additions);
        $expectedArray = array_merge([$addition], $additions);
        $this->assertSame($expectedArray, $this->formattedText->getAdditions(), 'addAddtions method should NOT clear existing addtions before inserting new additions');
    }

    public function testSetAdditions(): void
    {
        $this->assertEmpty($this->formattedText->getAdditions());

        $addition = new Link();
        $addition->setURL('http://www.example.com')->setRangeStart(0)->setRangeLength(10);
        $this->formattedText->addAddition($addition);

        $addition1 = new Link();
        $addition1->setURL('http://www.example1.com')->setRangeStart(1)->setRangeLength(11);

        $addition2 = new Link();
        $addition2->setURL('http://www.example2.com')->setRangeStart(2)->setRangeLength(12);

        $additions = [$addition1, $addition2];

        $this->formattedText->setAdditions($additions);
        $this->assertSame($additions, $this->formattedText->getAdditions(), 'setAddtions method should clear existing addtions first, then insert new addtions');
    }

    public function testAddInlineTextStyle(): void
    {
        $this->assertEmpty($this->formattedText->getInlineTextStyles());

        $inlineTextStyle = new InlineTextStyle();
        $inlineTextStyle->setRangeLength(10)->setRangeStart(1)->setTextStyle('caption-style');
        $this->formattedText->addInlineTextStyle($inlineTextStyle);
        $this->assertSame([$inlineTextStyle], $this->formattedText->getInlineTextStyles());
    }

    public function testAddInlineTextStlyeInvalid(): void
    {
        $this->expectException(AssertionFailedException::class);
        $inlineTextStyle = new InlineTextStyle();
        $inlineTextStyle->setRangeLength(10)->setRangeStart(1);
        $this->formattedText->addInlineTextStyle($inlineTextStyle);
    }

    public function testAddInlineTextStyles(): void
    {
        $inlineTextStyle1 = new InlineTextStyle();
        $inlineTextStyle1->setRangeLength(11)->setRangeStart(1)->setTextStyle('caption-style-1');

        $inlineTextStyle2 = new InlineTextStyle();
        $inlineTextStyle2->setRangeLength(12)->setRangeStart(2)->setTextStyle('caption-style-2');

        $inlineTextStyles = [$inlineTextStyle1, $inlineTextStyle2];
        $this->formattedText->addInlineTextStyles($inlineTextStyles);
        $this->assertSame($inlineTextStyles, $this->formattedText->getInlineTextStyles());

        $inlineTextStyle3 = new InlineTextStyle();
        $inlineTextStyle3->setRangeLength(13)->setRangeStart(3)->setTextStyle('caption-style-3');

        $inlineTextStyle4 = new InlineTextStyle();
        $inlineTextStyle4->setRangeLength(14)->setRangeStart(4)->setTextStyle('caption-style-4');

        $this->formattedText->addInlineTextStyles([$inlineTextStyle3, $inlineTextStyle4]);
        $expectedArray = array_merge($inlineTextStyles, [$inlineTextStyle3, $inlineTextStyle4]);
        $this->assertSame($expectedArray, $this->formattedText->getInlineTextStyles());
    }

    public function testSetInlineTextStyles(): void
    {
        $inlineTextStyle1 = new InlineTextStyle();
        $inlineTextStyle1->setRangeLength(10)->setRangeStart(1)->setTextStyle('caption-style');

        $inlineTextStyle2 = new InlineTextStyle();
        $inlineTextStyle2->setRangeLength(11)->setRangeStart(2)->setTextStyle('caption-style-1');

        $inlineTextStyles = [$inlineTextStyle1, $inlineTextStyle2];
        $this->formattedText->addInlineTextStyles($inlineTextStyles);
        $this->assertSame($inlineTextStyles, $this->formattedText->getInlineTextStyles());

        $inlineTextStyle3 = new InlineTextStyle();
        $inlineTextStyle3->setRangeLength(10)->setRangeStart(1)->setTextStyle('caption-style');

        $inlineTextStyle4 = new InlineTextStyle();
        $inlineTextStyle4->setRangeLength(11)->setRangeStart(2)->setTextStyle('caption-style-1');

        $this->formattedText->setInlineTextStyles([$inlineTextStyle3, $inlineTextStyle4]);
        $this->assertSame([$inlineTextStyle3, $inlineTextStyle4], $this->formattedText->getInlineTextStyles());
    }

    public function testValidate(): void
    {
        $this->formattedText->setText('this is a test');
        try {
            $this->formattedText->validate();
        } catch (\Exception $e) {
            $this->assertTrue(false);
        }

        $this->assertTrue(true, 'no exception should be thrown');
    }

    public function testValidationInvalid(): void
    {
        $this->expectException(AssertionFailedException::class);
        $inlineTextStyle = new InlineTextStyle();
        $inlineTextStyle->setRangeLength(10)->setRangeStart(1)->setTextStyle('caption-style');

        $this->formattedText->setFormat('node')->setInlineTextStyles([$inlineTextStyle]);
    }

    public function testJsonSerialize(): void
    {
        $expected = [
            'type'      => 'formatted_text',
            'text'      => '<strong>Name</strong> (last, first)',
            'textStyle' => 'good-style',
            'format'    => 'html',
        ];

        $this->formattedText
            ->setText('<strong>Name</strong> (last, first)')
            ->setFormat('html')
            ->setTextStyle('good-style');
        $this->assertJsonStringEqualsJsonString(json_encode($expected), json_encode($this->formattedText));
    }
}
