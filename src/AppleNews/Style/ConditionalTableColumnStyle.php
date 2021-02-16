<?php
declare(strict_types=1);

namespace Triniti\AppleNews\Style;

use Assert\Assertion;

/**
 * @link https://developer.apple.com/documentation/apple_news/conditional_table_column_style
 */
class ConditionalTableColumnStyle extends TableColumnStyle
{
    /** @var TableColumnSelector */
    protected $selectors;

    /**
     * @return TableColumnSelector
     */
    public function getSelector(): ?TableColumnSelector
    {
        return $this->selectors;
    }

    /**
     * @param TableColumnSelector $selectors
     *
     * @return static
     */
    public function setSelector(TableColumnSelector $selectors): self
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
