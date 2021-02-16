<?php
declare(strict_types=1);

namespace Triniti\Tests\AppleNews\Style;

use PHPUnit\Framework\TestCase;
use Triniti\AppleNews\Style\ConditionalTableColumnStyle;
use Triniti\AppleNews\Style\StrokeStyle;
use Triniti\AppleNews\Style\TableColumnStyle;

class TableColumnStyleTest extends TestCase
{
    protected TableColumnStyle $tableColumnStyle;

    public function setUp(): void
    {
        $this->tableColumnStyle = new TableColumnStyle();
    }

    public function testGetSetBackgroundColor(): void
    {
        $this->assertNull($this->tableColumnStyle->getBackgroundColor());
        $this->tableColumnStyle->setBackgroundColor('foo');
        $this->assertSame('foo', $this->tableColumnStyle->getBackgroundColor());
    }

    public function testGetSetConditional(): void
    {
        $this->assertNull($this->tableColumnStyle->getConditional());
        $conditional = new ConditionalTableColumnStyle();
        $this->tableColumnStyle->setConditional($conditional);
        $this->assertSame($conditional, $this->tableColumnStyle->getConditional());
    }

    public function testGetSetDivider(): void
    {
        $this->assertNull($this->tableColumnStyle->getDivider());
        $divider = new StrokeStyle();
        $this->tableColumnStyle->setDivider($divider);
        $this->assertSame($divider, $this->tableColumnStyle->getDivider());
    }

    public function testGetSetMinimumWidth(): void
    {
        $this->assertNull($this->tableColumnStyle->getMinimumWidth());
        $this->tableColumnStyle->setMinimumWidth(1);
        $this->assertSame(1, $this->tableColumnStyle->getMinimumWidth());
    }

    public function testGetSetWidth(): void
    {
        $this->markTestIncomplete();
        $this->assertNull($this->tableColumnStyle->getWidth());
        $this->tableColumnStyle->setWidth(1);
        $this->assertSame(1, $this->tableColumnStyle->getWidth());
    }
}
