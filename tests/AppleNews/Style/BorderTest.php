<?php
declare(strict_types=1);

namespace Triniti\Tests\AppleNews\Style;

use PHPUnit\Framework\TestCase;
use Triniti\AppleNews\Style\Border;
use Triniti\AppleNews\Style\StrokeStyle;
use Triniti\AppleNews\SupportedUnits;

class BorderTest extends TestCase
{
    protected Border $border;

    public function setUp(): void
    {
        $this->border = new Border();
    }

    public function testCreateBorder(): void
    {
        $this->assertInstanceOf(
            'Triniti\AppleNews\Style\Border',
            $this->border
        );

        $this->assertNull($this->border->getAll());
        $this->assertNull($this->border->getBottom());
        $this->assertNull($this->border->getLeft());
        $this->assertNull($this->border->getTop());
        $this->assertNull($this->border->getRight());
    }

    public function testSetAll(): void
    {
        $strokeStyle = new StrokeStyle();
        $strokeStyle->setWidth(new SupportedUnits('20pt'))->setColor('#abcdef');
        $this->border->setAll($strokeStyle);

        $this->assertSame($strokeStyle, $this->border->getAll());
    }

    public function testSetBottom(): void
    {
        $this->border->setBottom();
        $this->assertTrue($this->border->getBottom());

        $this->border->setBottom(false);
        $this->assertFalse($this->border->getBottom());
    }

    public function testSetLeft(): void
    {
        $this->border->setLeft(true);
        $this->assertTrue($this->border->getLeft());

        $this->border->setLeft(false);
        $this->assertFalse($this->border->getLeft());
    }

    public function testSetRight(): void
    {
        $this->border->setRight(true);
        $this->assertTrue($this->border->getRight());

        $this->border->setRight(false);
        $this->assertFalse($this->border->getRight());
    }

    public function testSetTop(): void
    {
        $this->border->setTop(true);
        $this->assertTrue($this->border->getTop());

        $this->border->setTop(false);
        $this->assertFalse($this->border->getTop());
    }

    public function testJsonSerialize(): void
    {
        $expected = [
            'all'   => [
                'width' => 2,
                'style' => 'dashed',
                'color' => '#FFFF00',
            ],
            'left'  => false,
            'right' => false,
        ];

        $strokeStyle = new StrokeStyle();
        $strokeStyle->setColor('#FFFF00')->setWidth(2)->setStyle('dashed');
        $this->border->setAll($strokeStyle)->setLeft(false)->setRight(false);

        $this->assertJsonStringEqualsJsonString(json_encode($expected), json_encode($this->border));
    }
}

