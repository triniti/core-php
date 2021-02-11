<?php
declare(strict_types=1);

namespace Triniti\AppleNews\Style;

use Assert\Assertion;

/**
 * @link https://developer.apple.com/documentation/apple_news/conditional_table_row_style
 */
class ConditionalTableRowStyle extends TableRowStyle
{
    protected ?TableRowSelector $selector = null;

    public function getSelector(): ?TableRowSelector
    {
        return $this->selector;
    }

    public function setSelector(TableRowSelector $selectors): self
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
