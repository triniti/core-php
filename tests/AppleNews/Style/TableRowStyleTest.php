<?php
declare(strict_types=1);

namespace Triniti\Tests\AppleNews\Style;

use PHPUnit\Framework\TestCase;
use Triniti\AppleNews\Style\ConditionalTableRowStyle;
use Triniti\AppleNews\Style\StrokeStyle;
use Triniti\AppleNews\Style\TableRowStyle;

class TableRowStyleTest extends TestCase
{
    protected TableRowStyle $tableRowStyle;

    public function setUp(): void
    {
        $this->tableRowStyle = new TableRowStyle();
    }

    public function testGetSetBackgroundColor(): void
    {
        $this->assertNull($this->tableRowStyle->getBackgroundColor());
        $this->tableRowStyle->setBackgroundColor('foo');
        $this->assertSame('foo', $this->tableRowStyle->getBackgroundColor());
    }

    public function testGetSetConditional(): void
    {
        $this->assertNull($this->tableRowStyle->getConditional());
        $conditional = new ConditionalTableRowStyle();
        $this->tableRowStyle->setConditional($conditional);
        $this->assertSame($conditional, $this->tableRowStyle->getConditional());
    }

    public function testGetSetDivider(): void
    {
        $this->assertNull($this->tableRowStyle->getDivider());
        $divider = new StrokeStyle();
        $this->tableRowStyle->setDivider($divider);
        $this->assertSame($divider, $this->tableRowStyle->getDivider());
    }

    public function testGetSeHeight(): void
    {
        $this->assertNull($this->tableRowStyle->getHeight());
        $this->tableRowStyle->setHeight(1);
        $this->assertSame(1, $this->tableRowStyle->getHeight());
    }
}
