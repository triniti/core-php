<?php
declare(strict_types=1);

namespace Triniti\Tests\AppleNews;

use Assert\AssertionFailedException;
use PHPUnit\Framework\TestCase;
use Triniti\AppleNews\CaptionDescriptor;
use Triniti\AppleNews\Link\Link;
use Triniti\AppleNews\Style\ComponentTextStyle;
use Triniti\AppleNews\Style\InlineTextStyle;

class CaptionDescriptorTest extends TestCase
{
    protected CaptionDescriptor $captionDescriptor;

    protected function setup(): void
    {
        $this->captionDescriptor = new CaptionDescriptor();
    }

    public function testSetTextStyle(): void
    {
        $this->assertNull($this->captionDescriptor->getTextStyle());

        $this->captionDescriptor->setTextStyle('style-string');
        $this->assertSame('style-string', $this->captionDescriptor->getTextStyle());

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
        $this->captionDescriptor->setTextStyle($textStyle);
        $this->assertSame($textStyle, $this->captionDescriptor->getTextStyle());
    }

    public function testSetFormat(): void
    {
        $this->assertNull($this->captionDescriptor->getFormat());

        $this->captionDescriptor->setFormat('markdown');
        $this->assertSame('markdown', $this->captionDescriptor->getFormat());

        $this->captionDescriptor->setFormat('html');
        $this->assertSame('html', $this->captionDescriptor->getFormat());

        $this->captionDescriptor->setFormat('none');
        $this->assertSame('none', $this->captionDescriptor->getFormat());
    }

    public function testSetFormatInvalid(): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->captionDescriptor->setFormat('invalid');
    }

    public function testSetText(): void
    {
        $this->assertNull($this->captionDescriptor->getText());

        $this->captionDescriptor->setText('hello world');
        $this->assertSame('hello world', $this->captionDescriptor->getText());
    }

    public function testAddAddition(): void
    {
        $this->assertEmpty($this->captionDescriptor->getAdditions());

        $addition = new Link();
        $addition->setURL('http://www.example.com')->setRangeStart(0)->setRangeLength(10);

        $this->captionDescriptor->addAddition($addition);
        $this->assertSame([$addition], $this->captionDescriptor->getAdditions());
    }

    public function testAddAdditionInvalid(): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->assertEmpty($this->captionDescriptor->getAdditions());

        $addition = new Link();
        $addition->setRangeStart(0)->setRangeLength(10);

        $this->captionDescriptor->addAddition($addition);
    }

    public function testAddAdditions(): void
    {
        $this->assertEmpty($this->captionDescriptor->getAdditions());

        $addition = new Link();
        $addition->setURL('http://www.example.com')->setRangeStart(0)->setRangeLength(10);
        $this->captionDescriptor->addAddition($addition);

        $addition1 = new Link();
        $addition1->setURL('http://www.example1.com')->setRangeStart(1)->setRangeLength(11);

        $addition2 = new Link();
        $addition2->setURL('http://www.example2.com')->setRangeStart(2)->setRangeLength(12);

        $additions = [$addition1, $addition2];

        $this->captionDescriptor->addAdditions($additions);
        $expectedArray = array_merge([$addition], $additions);
        $this->assertSame($expectedArray, $this->captionDescriptor->getAdditions(), 'addAddtions method should NOT clear existing addtions before inserting new additions');
    }

    public function testSetAdditions(): void
    {
        $this->assertEmpty($this->captionDescriptor->getAdditions());

        $addition = new Link();
        $addition->setURL('http://www.example.com')->setRangeStart(0)->setRangeLength(10);
        $this->captionDescriptor->addAddition($addition);

        $addition1 = new Link();
        $addition1->setURL('http://www.example1.com')->setRangeStart(1)->setRangeLength(11);

        $addition2 = new Link();
        $addition2->setURL('http://www.example2.com')->setRangeStart(2)->setRangeLength(12);

        $additions = [$addition1, $addition2];

        $this->captionDescriptor->setAdditions($additions);
        $this->assertSame($additions, $this->captionDescriptor->getAdditions(), 'setAddtions method should clear existing addtions first, then insert new addtions');
    }

    public function testAddInlineTextStyle(): void
    {
        $this->assertEmpty($this->captionDescriptor->getInlineTextStyles());

        $inlineTextStyle = new InlineTextStyle();
        $inlineTextStyle->setRangeLength(10)->setRangeStart(1)->setTextStyle('caption-style');
        $this->captionDescriptor->addInlineTextStyle($inlineTextStyle);
        $this->assertSame([$inlineTextStyle], $this->captionDescriptor->getInlineTextStyles());
    }

    public function testAddInlineTextStlyeInvalid(): void
    {
        $this->expectException(AssertionFailedException::class);
        $inlineTextStyle = new InlineTextStyle();
        $inlineTextStyle->setRangeLength(10)->setRangeStart(1);
        $this->captionDescriptor->addInlineTextStyle($inlineTextStyle);
    }

    public function testAddInlineTextStyles(): void
    {
        $inlineTextStyle1 = new InlineTextStyle();
        $inlineTextStyle1->setRangeLength(11)->setRangeStart(1)->setTextStyle('caption-style-1');

        $inlineTextStyle2 = new InlineTextStyle();
        $inlineTextStyle2->setRangeLength(12)->setRangeStart(2)->setTextStyle('caption-style-2');

        $inlineTextStyles = [$inlineTextStyle1, $inlineTextStyle2];
        $this->captionDescriptor->addInlineTextStyles($inlineTextStyles);
        $this->assertSame($inlineTextStyles, $this->captionDescriptor->getInlineTextStyles());

        $inlineTextStyle3 = new InlineTextStyle();
        $inlineTextStyle3->setRangeLength(13)->setRangeStart(3)->setTextStyle('caption-style-3');

        $inlineTextStyle4 = new InlineTextStyle();
        $inlineTextStyle4->setRangeLength(14)->setRangeStart(4)->setTextStyle('caption-style-4');

        $this->captionDescriptor->addInlineTextStyles([$inlineTextStyle3, $inlineTextStyle4]);
        $expectedArray = array_merge($inlineTextStyles, [$inlineTextStyle3, $inlineTextStyle4]);
        $this->assertSame($expectedArray, $this->captionDescriptor->getInlineTextStyles());
    }

    public function testSetInlineTextStyles(): void
    {
        $inlineTextStyle1 = new InlineTextStyle();
        $inlineTextStyle1->setRangeLength(10)->setRangeStart(1)->setTextStyle('caption-style');

        $inlineTextStyle2 = new InlineTextStyle();
        $inlineTextStyle2->setRangeLength(11)->setRangeStart(2)->setTextStyle('caption-style-1');

        $inlineTextStyles = [$inlineTextStyle1, $inlineTextStyle2];
        $this->captionDescriptor->addInlineTextStyles($inlineTextStyles);
        $this->assertSame($inlineTextStyles, $this->captionDescriptor->getInlineTextStyles());

        $inlineTextStyle3 = new InlineTextStyle();
        $inlineTextStyle3->setRangeLength(10)->setRangeStart(1)->setTextStyle('caption-style');

        $inlineTextStyle4 = new InlineTextStyle();
        $inlineTextStyle4->setRangeLength(11)->setRangeStart(2)->setTextStyle('caption-style-1');

        $this->captionDescriptor->setInlineTextStyles([$inlineTextStyle3, $inlineTextStyle4]);
        $this->assertSame([$inlineTextStyle3, $inlineTextStyle4], $this->captionDescriptor->getInlineTextStyles());
    }

    public function testJsonSerialize(): void
    {
        $expected = [
            'text'      => '<a href="https://example.com/animals/steenbok">Steenbok</a> typically <span data-anf-textstyle="highlight">lie low</span> in vegetation cover at the first sign of threat.',
            'textStyle' => 'caption-style',
            'format'    => 'html',
        ];

        $this->captionDescriptor
            ->setText('<a href="https://example.com/animals/steenbok">Steenbok</a> typically <span data-anf-textstyle="highlight">lie low</span> in vegetation cover at the first sign of threat.')
            ->setFormat('html')
            ->setTextStyle('caption-style');
        $this->assertJsonStringEqualsJsonString(json_encode($expected), json_encode($this->captionDescriptor));
    }

    public function testValidate(): void
    {
        $this->captionDescriptor->setText('this is a test');
        try {
            $this->captionDescriptor->validate();
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

        $this->captionDescriptor->setFormat('node')->setInlineTextStyles([$inlineTextStyle]);
    }
}
