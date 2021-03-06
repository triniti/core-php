<?php
declare(strict_types=1);

namespace Triniti\AppleNews\Style;

use Assert\Assertion;

/**
 * @link https://developer.apple.com/documentation/apple_news/conditional_table_row_style
 */
class ConditionalTableRowStyle extends TableRowStyle
{
    /** @var TableRowSelector */
    protected $selectors;

    /**
     * @return TableRowSelector
     */
    public function getSelectors(): ?TableRowSelector
    {
        return $this->selectors;
    }

    /**
     * @param TableRowSelector $selectors
     *
     * @return static
     */
    public function setSelectors(TableRowSelector $selectors): self
    {
        $this->selectors = $selectors;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function validate(): void
    {
        parent::validate();
        Assertion::notNull($this->selectors);
    }
}
