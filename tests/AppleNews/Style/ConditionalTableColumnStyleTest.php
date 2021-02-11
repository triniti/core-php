<?php
declare(strict_types=1);

namespace Triniti\Tests\AppleNews\Style;

use Assert\InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Triniti\AppleNews\Style\ConditionalTableColumnStyle;
use Triniti\AppleNews\Style\TableColumnSelector;

class ConditionalTableColumnStyleTest extends TestCase
{
    public function testGetSetSelectors(): void
    {
        $conditionalTableColumnStyle = new ConditionalTableColumnStyle();
        $this->assertNull($conditionalTableColumnStyle->getSelector());
        $tableColumnSelector = new TableColumnSelector();
        $conditionalTableColumnStyle->setSelector($tableColumnSelector);
        $this->assertEquals($tableColumnSelector, $conditionalTableColumnStyle->getSelector());
    }

    public function testValidate(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new ConditionalTableColumnStyle())->validate();
    }
}
