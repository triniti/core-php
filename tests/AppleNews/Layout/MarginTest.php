<?php
declare(strict_types=1);

namespace Triniti\Tests\AppleNews\Layout;

use PHPUnit\Framework\TestCase;
use Triniti\AppleNews\Layout\Margin;
use Triniti\AppleNews\SupportedUnits;

class MarginTest extends TestCase
{
    protected Margin $margin;

    public function setUp(): void
    {
        $this->margin = new Margin();
    }

    public function testCreateMargin(): void
    {
        $this->assertInstanceOf(
            'Triniti\AppleNews\Layout\Margin',
            $this->margin
        );

        $this->assertNull($this->margin->getTop());
        $this->assertNull($this->margin->getBottom());
    }

    public function testSetTop(): void
    {
        $this->margin->setTop(1);
        $this->assertEquals(1, $this->margin->getTop());

        $marginUnit = new SupportedUnits('1dg');
        $this->margin->setTop($marginUnit);
        $this->assertSame($marginUnit, $this->margin->getTop());
    }

    public function testSetBottom(): void
    {
        $this->margin->setBottom(1);
        $this->assertEquals(1, $this->margin->getBottom());

        $marginUnit = new SupportedUnits('1dg');
        $this->margin->setBottom($marginUnit);
        $this->assertSame($marginUnit, $this->margin->getBottom());
    }

    public function testJsonSerialize(): void
    {
        $expected = [
            'top'    => 1,
            'bottom' => 1,
        ];

        $this->margin->setTop(1)->setBottom(1);

        $this->assertJsonStringEqualsJsonString(json_encode($expected), json_encode($this->margin));
    }
}
