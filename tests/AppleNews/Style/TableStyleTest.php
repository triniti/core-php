<?php
declare(strict_types=1);

namespace Triniti\Tests\AppleNews\Style;

use PHPUnit\Framework\TestCase;
use Triniti\AppleNews\Style\TableCellStyle;
use Triniti\AppleNews\Style\TableColumnStyle;
use Triniti\AppleNews\Style\TableRowStyle;
use Triniti\AppleNews\Style\TableStyle;

class TableStyleTest extends TestCase
{
    protected TableStyle $tableStyle;

    public function setUp(): void
    {
        $this->tableStyle = new TableStyle();
    }

    public function testGetSetCell(): void
    {
        $this->assertNull($this->tableStyle->getCell());
        $cell = new TableCellStyle();
        $this->tableStyle->setCell($cell);
        $this->assertSame($cell, $this->tableStyle->getCell());
    }

    public function testGetSetColumns(): void
    {
        $this->assertNull($this->tableStyle->getColumns());
        $columns = new TableColumnStyle();
        $this->tableStyle->setColumns($columns);
        $this->assertSame($columns, $this->tableStyle->getColumns());
    }

    public function testGetSetHeaderCells(): void
    {
        $this->assertNull($this->tableStyle->getHeaderCells());
        $cell = new TableCellStyle();
        $this->tableStyle->setHeaderCells($cell);
        $this->assertSame($cell, $this->tableStyle->getHeaderCells());
    }

    public function testGetSetHeaderColumns(): void
    {
        $this->assertNull($this->tableStyle->getHeaderColumns());
        $columns = new TableColumnStyle();
        $this->tableStyle->setHeaderColumns($columns);
        $this->assertSame($columns, $this->tableStyle->getHeaderColumns());
    }

    public function testGetSetHeadRows(): void
    {
        $this->assertNull($this->tableStyle->getHeadRows());
        $rows = new TableRowStyle();
        $this->tableStyle->setHeadRows($rows);
        $this->assertSame($rows, $this->tableStyle->getHeadRows());
    }

    public function testGetSetRows(): void
    {
        $this->assertNull($this->tableStyle->getRows());
        $rows = new TableRowStyle();
        $this->tableStyle->setRows($rows);
        $this->assertSame($rows, $this->tableStyle->getRows());
    }
}
