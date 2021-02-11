<?php
declare(strict_types=1);

namespace Triniti\AppleNews;

use Assert\Assertion;

/**
 * @link https://developer.apple.com/documentation/apple_news/data_table_sorting
 */
class DataTableSorting extends AppleNewsObject
{
    protected ?string $direction = null;

    /**
     * The identifier property of one of the table’s data descriptors
     */
    protected ?string $descriptor = null;

    public function getDescriptor(): ?string
    {
        return $this->descriptor;
    }

    public function setDescriptor(string $descriptor): self
    {
        $this->descriptor = $descriptor;
        return $this;
    }

    public function getDirection(): ?string
    {
        return $this->direction;
    }

    public function setDirection(string $direction): self
    {
        Assertion::inArray($direction, ['ascending', 'descending']);
        $this->direction = $direction;
        return $this;
    }

    public function jsonSerialize()
    {
        return $this->getSetProperties();
    }

    public function validate(): void
    {
        Assertion::notNull($this->descriptor);
        Assertion::notNull($this->direction);
    }
}
