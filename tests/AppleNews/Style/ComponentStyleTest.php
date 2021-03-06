<?php
declare(strict_types=1);

namespace Triniti\Tests\AppleNews\Style;

use Assert\AssertionFailedException;
use PHPUnit\Framework\TestCase;
use Triniti\AppleNews\Style\Border;
use Triniti\AppleNews\Style\ComponentStyle;
use Triniti\AppleNews\Style\ImageFill;
use Triniti\AppleNews\Style\StrokeStyle;
use Triniti\AppleNews\Style\TableStyle;

class ComponentStyleTest extends TestCase
{
    protected ComponentStyle $componentStyle;

    public function setUp(): void
    {
        $this->componentStyle = new ComponentStyle();
    }

    public function testCreateComponentStyle(): void
    {
        $this->assertInstanceOf('Triniti\AppleNews\Style\ComponentStyle', $this->componentStyle);

        $this->assertNull($this->componentStyle->getBorder());
        $this->assertNull($this->componentStyle->getFill());
        $this->assertNull($this->componentStyle->getTableStyle());
        $this->assertEquals(1.0, $this->componentStyle->getOpacity());
        $this->assertNull($this->componentStyle->getBackgroundColor());
    }

    public function testSetBackgroundColor(): void
    {
        $this->componentStyle->setBackgroundColor('#FF11337F');
        $this->assertEquals('#FF11337F', $this->componentStyle->getBackgroundColor());
    }

    public function testSetFill(): void
    {
        $fill = new ImageFill();
        $fill
            ->setURL('http://www.test.com/1.jpg')
            ->setFillMode()
            ->setVerticalAlignment('top');

        $this->componentStyle->setFill($fill);
        $this->assertSame($fill, $this->componentStyle->getFill());
    }

    public function testSetOpacity(): void
    {
        $this->assertEquals(1, $this->componentStyle->getOpacity());
        $this->componentStyle->setOpacity(0.5);
        $this->assertEquals(0.5, $this->componentStyle->getOpacity());
        $this->componentStyle->setOpacity();
        $this->assertEquals(1, $this->componentStyle->getOpacity());
    }

    public function testSetOpacityInvalid(): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->componentStyle->setOpacity(1.2);
    }

    public function testSetTableStyle(): void
    {
        $tableStyle = new TableStyle();
        $this->componentStyle->setTableStyle($tableStyle);
        $this->assertSame($tableStyle, $this->componentStyle->getTableStyle());
    }

    public function testJsonSerialize(): void
    {
        $expectedJson = '{
            "backgroundColor": "#FFFFFF",
            "opacity": 1,
            "border": {
                 "all": {
                   "width": 1,
                   "style": "solid"
                 },
                 "left": false,
                 "right": false
                }
        }';

        $boder = new Border();
        $boder->setAll(new StrokeStyle())->setLeft(false)->setRight(false);
        $this->componentStyle
            ->setBackgroundColor('#FFFFFF')
            ->setBorder($boder);

        $this->assertJsonStringEqualsJsonString($expectedJson, json_encode($this->componentStyle));
    }
}

