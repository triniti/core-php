<?php
declare(strict_types=1);

namespace Triniti\Tests\AppleNews\Style;

use Assert\InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Triniti\AppleNews\Style\ConditionalTableRowStyle;
use Triniti\AppleNews\Style\TableRowSelector;

class ConditionalTableRowStyleTest extends TestCase
{
    public function testGetSetSelectors(): void
    {
        $conditionalTableRowStyle = new ConditionalTableRowStyle();
        $this->assertNull($conditionalTableRowStyle->getSelector());
        $tableRowSelector = new TableRowSelector();
        $conditionalTableRowStyle->setSelector($tableRowSelector);
        $this->assertEquals($tableRowSelector, $conditionalTableRowStyle->getSelector());
    }

    public function testValidate(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new ConditionalTableRowStyle())->validate();
    }
}
