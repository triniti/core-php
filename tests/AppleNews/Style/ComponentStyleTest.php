<?php
declare(strict_types=1);

namespace Triniti\Tests\AppleNews\Style;

use Triniti\AppleNews\Condition;
use Assert\AssertionFailedException;
use PHPUnit\Framework\TestCase;
use Triniti\AppleNews\Style\Border;
use Triniti\AppleNews\Style\ComponentStyle;
use Triniti\AppleNews\Style\ConditionalComponentStyle;
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

    /**
     * @test testGetSetAddConditional
     */
    public function testGetSetAddConditional()
    {
        $conditional1 = new ConditionalComponentStyle();
        $condition1 = new Condition();
        $condition1->setMaxColumns(1);
        $conditional1->addCondition($condition1);


        $conditional2 = new ConditionalComponentStyle();
        $condition2 = new Condition();
        $condition2->setPlatform('any');
        $conditional2->addCondition($condition2);

        $conditional = [$conditional1];

        $this->componentStyle->setConditional($conditional);
        $this->assertEquals($conditional, $this->componentStyle->getConditional());

        $conditional[] = $conditional2;
        $this->componentStyle->addConditionals($conditional);
        $this->assertEquals([$conditional1, $conditional1, $conditional2], $this->componentStyle->getConditional());

        $this->componentStyle->addConditionals();
        $this->assertEquals([$conditional1, $conditional1, $conditional2], $this->componentStyle->getConditional());

        $this->componentStyle->addConditionals(null);
        $this->assertEquals([$conditional1, $conditional1, $conditional2], $this->componentStyle->getConditional());

        $this->componentStyle->setConditional();
        $this->assertEquals([], $this->componentStyle->getConditional());

        $this->componentStyle->setConditional(null);
        $this->assertEquals([], $this->componentStyle->getConditional());
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

