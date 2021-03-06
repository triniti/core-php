<?php
declare(strict_types=1);

namespace Triniti\AppleNews\Style;

use Assert\Assertion;

/**
 * @link https://developer.apple.com/documentation/apple_news/conditional_table_cell_style
 */
class ConditionalTableCellStyle extends TableCellStyle
{
    /** @var TableCellSelector */
    protected $selectors;

    /**
     * @return TableCellSelector
     */
    public function getSelectors(): ?TableCellSelector
    {
        return $this->selectors;
    }

    /**
     * @param TableCellSelector $selectors
     *
     * @return static
     */
    public function setSelectors(TableCellSelector $selectors): self
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
