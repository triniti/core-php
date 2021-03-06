<?php
declare(strict_types=1);

namespace Triniti\Tests\AppleNews\Style;

use Assert\InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Triniti\AppleNews\Style\TableRowSelector;

class TableRowSelectorTest extends TestCase
{
    protected TableRowSelector $tableRowSelector;

    public function setUp(): void
    {
        $this->tableRowSelector = new TableRowSelector();
    }

    public function testGetSetRowIndex(): void
    {
        $this->assertNull($this->tableRowSelector->getRowIndex());
        $this->tableRowSelector->setRowIndex(1);
        $this->assertSame(1, $this->tableRowSelector->getRowIndex());

        $this->expectException(InvalidArgumentException::class);
        $this->tableRowSelector->setRowIndex(-1);
    }

    public function testGetSetDescriptor(): void
    {
        $this->assertNull($this->tableRowSelector->getDescriptor());
        $this->tableRowSelector->setDescriptor('foo');
        $this->assertSame('foo', $this->tableRowSelector->getDescriptor());
    }

    public function testGetSetOdd(): void
    {
        $this->assertNull($this->tableRowSelector->getOdd());
        $this->tableRowSelector->setOdd(true);
        $this->assertTrue($this->tableRowSelector->getOdd());
    }

    public function testGetSetEven(): void
    {
        $this->assertNull($this->tableRowSelector->getEven());
        $this->tableRowSelector->setEven(true);
        $this->assertTrue($this->tableRowSelector->getEven());
    }
}
