<?php
declare(strict_types=1);

namespace Triniti\Tests\AppleNews\Style;

use Assert\InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Triniti\AppleNews\Style\ConditionalTableCellStyle;
use Triniti\AppleNews\Style\TableCellSelector;

class ConditionalTableCellStyleTest extends TestCase
{
    public function testGetSetSelectors(): void
    {
        $this->markTestIncomplete();

        $conditionalTableCellStyle = new ConditionalTableCellStyle();
        $this->assertNull($conditionalTableCellStyle->getSelector());
        $tableCellSelector = new TableCellSelector();
        $conditionalTableCellStyle->setSelector($tableCellSelector);
        $this->assertEquals($tableCellSelector, $conditionalTableCellStyle->getSelector());
    }

    public function testValidate(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new ConditionalTableCellStyle())->validate();
    }
}
