<?php
declare(strict_types=1);

namespace Triniti\Tests\AppleNews\Style;

use Assert\InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Triniti\AppleNews\Style\TableColumnSelector;

class TableColumnSelectorTest extends TestCase
{
    protected TableColumnSelector $tableColumnSelector;

    public function setUp(): void
    {
        $this->tableColumnSelector = new TableColumnSelector();
    }

    public function testGetSetColumnIndex(): void
    {
        $this->assertNull($this->tableColumnSelector->getColumnIndex());
        $this->tableColumnSelector->setColumnIndex(1);
        $this->assertSame(1, $this->tableColumnSelector->getColumnIndex());

        $this->expectException(InvalidArgumentException::class);
        $this->tableColumnSelector->setColumnIndex(-1);
    }

    public function testGetSetDescriptor(): void
    {
        $this->assertNull($this->tableColumnSelector->getDescriptor());
        $this->tableColumnSelector->setDescriptor('foo');
        $this->assertSame('foo', $this->tableColumnSelector->getDescriptor());
    }

    public function testGetSetOdd(): void
    {
        $this->assertNull($this->tableColumnSelector->getOdd());
        $this->tableColumnSelector->setOdd(true);
        $this->assertTrue($this->tableColumnSelector->getOdd());
    }

    public function testGetSetEven(): void
    {
        $this->assertNull($this->tableColumnSelector->getEven());
        $this->tableColumnSelector->setEven(true);
        $this->assertTrue($this->tableColumnSelector->getEven());
    }
}
