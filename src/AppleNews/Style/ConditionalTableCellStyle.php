<?php
declare(strict_types=1);

namespace Triniti\AppleNews\Style;

use Assert\Assertion;

/**
 * @link https://developer.apple.com/documentation/apple_news/conditional_table_cell_style
 */
class ConditionalTableCellStyle extends TableCellStyle
{
    protected ?TableCellSelector $selector = null;

    public function getSelector(): ?TableCellSelector
    {
        return $this->selector;
    }

    public function setSelector(TableCellSelector $selectors): self
    {
        $this->selector = $selectors;
        return $this;
    }

    public function validate(): void
    {
        parent::validate();
        Assertion::notNull($this->selector);
    }
}
