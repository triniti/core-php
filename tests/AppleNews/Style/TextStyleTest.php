<?php
declare(strict_types=1);

namespace Triniti\Tests\AppleNews\Style;

use Assert\AssertionFailedException;
use Assert\InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Triniti\AppleNews\Style\ListItemStyle;
use Triniti\AppleNews\Style\Offset;
use Triniti\AppleNews\Style\Shadow;
use Triniti\AppleNews\Style\TextDecoration;
use Triniti\AppleNews\Style\TextStrokeStyle;
use Triniti\AppleNews\Style\TextStyle;

class TextStyleTest extends TestCase
{
    protected TextStyle $textStyle;

    public function setUp(): void
    {
        $this->textStyle = new TextStyle();
    }

    public function testCreateTextStyle(): void
    {
        $this->assertInstanceOf(
            'Triniti\AppleNews\Style\TextStyle',
            $this->textStyle
        );
    }

    public function testSetBackground(): void
    {
        $this->assertNull($this->textStyle->getBackgroundColor());

        $this->textStyle->setBackgroundColor('blue');
        $this->assertSame('blue', $this->textStyle->getBackgroundColor());
    }

    public function testGetSetFontFamily(): void
    {
        $this->assertNull($this->textStyle->getFontFamily());
        $this->textStyle->setFontFamily('foo');
        $this->assertSame('foo', $this->textStyle->getFontFamily());
    }

    public function testGetSetFontSize(): void
    {
        $this->assertNull($this->textStyle->getFontSize());
        $this->textStyle->setFontSize(1);
        $this->assertSame(1, $this->textStyle->getFontSize());
    }

    public function testGetSetFontStyle(): void
    {
        $this->assertNull($this->textStyle->getFontStyle());
        $this->textStyle->setFontStyle('italic');
        $this->assertSame('italic', $this->textStyle->getFontStyle());

        $this->expectException(InvalidArgumentException::class);
        $this->textStyle->setFontStyle('foo');
    }

    public function testGetSetFontWidth(): void
    {
        $this->assertNull($this->textStyle->getFontWidth());
        $this->textStyle->setFontWidth('condensed');
        $this->assertSame('condensed', $this->textStyle->getFontWidth());

        $this->expectException(InvalidArgumentException::class);
        $this->textStyle->setFontWidth('foo');
    }

    public function testGetSetStroke(): void
    {
        $this->assertNull($this->textStyle->getStroke());
        $stroke = new TextStrokeStyle();
        $this->textStyle->setStroke($stroke);
        $this->assertSame($stroke, $this->textStyle->getStroke());
    }

    public function testGetSetTextColor(): void
    {
        $this->assertNull($this->textStyle->getTextColor());
        $this->textStyle->setTextColor('foo');
        $this->assertSame('foo', $this->textStyle->getTextColor());
    }

    public function testGetSetShadow(): void
    {
        $this->assertNull($this->textStyle->getTextShadow());
        $shadow = new Shadow();
        $this->textStyle->setTextShadow($shadow);
        $this->assertSame($shadow, $this->textStyle->getTextShadow());
    }

    public function testGetSetTracking(): void
    {
        $this->assertNull($this->textStyle->getTracking());
        $this->textStyle->setTracking(1.0);
        $this->assertSame(1.0, $this->textStyle->getTracking());
    }

    public function testGetSetUnorderedListItems(): void
    {
        $this->assertNull($this->textStyle->getUnorderedListItems());
        $style = new ListItemStyle();
        $this->textStyle->setUnorderedListItems($style);
        $this->assertSame($style, $this->textStyle->getUnorderedListItems());
    }

    public function testSetFontName(): void
    {
        $this->assertNull($this->textStyle->getFontName());

        $this->textStyle->setFontName('GillSans-Bold');
        $this->assertSame('GillSans-Bold', $this->textStyle->getFontName());
    }

    public function testSetFontWeight(): void
    {
        $this->assertNull($this->textStyle->getFontWeight());

        $this->textStyle->setFontWeight('medium');
        $this->assertSame('medium', $this->textStyle->getFontWeight());

        $this->textStyle->setFontWeight(800);
        $this->assertSame(800, $this->textStyle->getFontWeight());
    }

    public function testSetFontWeightInvalid(): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->textStyle->setFontWeight('invalid');
    }

    public function testSetOrderedListItems(): void
    {
        $this->assertNull($this->textStyle->getOrderedListItems());

        $listItemStyle = new ListItemStyle();
        $listItemStyle->setType('upper_roman');
        $this->textStyle->setOrderedListItems($listItemStyle);

        $this->assertSame($listItemStyle, $this->textStyle->getOrderedListItems());
    }

    public function testSetUnderline(): void
    {
        $this->assertNull($this->textStyle->getUnderline());

        $this->textStyle->setUnderline(true);
        $this->assertTrue($this->textStyle->getUnderline());

        $td = new TextDecoration();
        $td->setColor('#111111');
        $this->textStyle->setUnderline($td);
        $this->assertSame($td, $this->textStyle->getUnderline());
    }

    public function testSetVerticalAlignment(): void
    {
        $this->assertNull($this->textStyle->getVerticalAlignment());

        $this->textStyle->setVerticalAlignment('subscript');
        $this->assertSame('subscript', $this->textStyle->getVerticalAlignment());

        $this->textStyle->setVerticalAlignment();
        $this->assertSame('baseline', $this->textStyle->getVerticalAlignment());
    }

    public function testSetVerticalAlignmentInvalid(): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->textStyle->setVerticalAlignment('invalid');
    }

    public function testJsonSerialize(): void
    {
        $expected = [
            'fontFamily' => 'Gill Sans',
            'fontWeight' => 'bolder',
            'textColor'  => '#333333',
            'fontSize'   => 12,
            'textShadow' => [
                'radius'  => 20,
                'opacity' => 0.3,
                'color'   => '#99999930',
                'offset'  => [
                    'x' => -10,
                    'y' => 5,
                ],
            ],
            'stroke'     => [
                'color' => '#FFC800',
                'width' => 1,
            ],
        ];

        $offset = new Offset();
        $offset->setX(-10)->setY(5);
        $shadow = new Shadow();
        $shadow->setOpacity(0.3)->setRadius(20)->setColor('#99999930')->setOffset($offset);

        $stroke = new TextStrokeStyle();
        $stroke->setWidth(1)->setColor('#FFC800');

        $this->textStyle
            ->setFontFamily('Gill Sans')
            ->setFontWeight('bolder')
            ->setTextColor('#333333')
            ->setFontSize(12)
            ->setTextShadow($shadow)
            ->setStroke($stroke);
        $this->assertJsonStringEqualsJsonString(json_encode($expected), json_encode($this->textStyle));
    }

}
