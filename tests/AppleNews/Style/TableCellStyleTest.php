<?php
declare(strict_types=1);

namespace Triniti\Tests\AppleNews\Style;

use Assert\InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Triniti\AppleNews\Style\Border;
use Triniti\AppleNews\Style\ConditionalTableCellStyle;
use Triniti\AppleNews\Style\TableCellStyle;

class TableCellStyleTest extends TestCase
{
    protected TableCellStyle $tableCellStyle;

    public function setUp(): void
    {
        $this->tableCellStyle = new TableCellStyle();
    }

    public function testGetSetBackgroundColor(): void
    {
        $this->assertNull($this->tableCellStyle->getBackgroundColor());
        $this->tableCellStyle->setBackgroundColor('black');
        $this->assertSame('black', $this->tableCellStyle->getBackgroundColor());
    }

    public function testGetSetBorder(): void
    {
        $this->assertNull($this->tableCellStyle->getBorder());
        $border = new Border();
        $this->tableCellStyle->setBorder($border);
        $this->assertSame($border, $this->tableCellStyle->getBorder());
    }

    public function testGetSetConditional(): void
    {
        $this->assertNull($this->tableCellStyle->getConditional());
        $conditional = new ConditionalTableCellStyle();
        $this->tableCellStyle->setConditional($conditional);
        $this->assertSame($conditional, $this->tableCellStyle->getConditional());
    }

    public function testGetSetHeight(): void
    {
        $this->assertNull($this->tableCellStyle->getHeight());
        $this->tableCellStyle->setHeight(2);
        $this->assertSame(2, $this->tableCellStyle->getHeight());
    }

    public function testGetSetHorizontalAlignment(): void
    {
        $this->assertNull($this->tableCellStyle->getHorizontalAlignment());
        $this->tableCellStyle->setHorizontalAlignment('center');
        $this->assertSame('center', $this->tableCellStyle->getHorizontalAlignment());

        $this->expectException(InvalidArgumentException::class);
        $this->tableCellStyle->setHorizontalAlignment('foo');
    }

    public function testGetSetMinimumWidth(): void
    {
        $this->assertNull($this->tableCellStyle->getMinimumWidth());
        $this->tableCellStyle->setMinimumWidth(2);
        $this->assertSame(2, $this->tableCellStyle->getMinimumWidth());
    }

    public function testGetSetPadding(): void
    {
        $this->assertNull($this->tableCellStyle->getPadding());
        $this->tableCellStyle->setPadding(2);
        $this->assertSame(2, $this->tableCellStyle->getPadding());
    }

    public function testGetSetTextStyle(): void
    {
        $this->assertNull($this->tableCellStyle->getTextStyle());
        $this->tableCellStyle->setTextStyle('foo');
        $this->assertSame('foo', $this->tableCellStyle->getTextStyle());
    }

    public function testGetSetVerticalAlignment(): void
    {
        $this->assertNull($this->tableCellStyle->getVerticalAlignment());
        $this->tableCellStyle->setVerticalAlignment('center');
        $this->assertSame('center', $this->tableCellStyle->getVerticalAlignment());

        $this->expectException(InvalidArgumentException::class);
        $this->tableCellStyle->setVerticalAlignment('foo');
    }

    public function testGetSetWidth(): void
    {
        $this->assertNull($this->tableCellStyle->getWidth());
        $this->tableCellStyle->setWidth(2);
        $this->assertSame(2, $this->tableCellStyle->getWidth());
    }
}
