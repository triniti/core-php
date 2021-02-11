<?php
declare(strict_types=1);

namespace Triniti\AppleNews\Style;

use Assert\Assertion;

/**
 * @link https://developer.apple.com/documentation/apple_news/conditional_table_column_style
 */
class ConditionalTableColumnStyle extends TableColumnStyle
{
    protected ?TableColumnSelector $selector = null;

    public function getSelector(): ?TableColumnSelector
    {
        return $this->selector;
    }

    public function setSelector(TableColumnSelector $selectors): self
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
