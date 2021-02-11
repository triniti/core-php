<?php
declare(strict_types=1);

namespace Triniti\AppleNews\Style;

use Assert\Assertion;
use Triniti\AppleNews\AppleNewsObject;

/**
 * @link https://developer.apple.com/documentation/apple_news/table_column_selector
 */
class TableColumnSelector extends AppleNewsObject
{
    protected ?int $columnIndex = null;
    protected ?string $descriptor = null;
    protected ?bool $odd = null;
    protected ?bool $even = null;

    public function getColumnIndex(): ?int
    {
        return $this->columnIndex;
    }

    public function setColumnIndex(int $columnIndex): self
    {
        Assertion::greaterOrEqualThan($columnIndex, 0);
        $this->columnIndex = $columnIndex;
        return $this;
    }

    public function getDescriptor(): ?string
    {
        return $this->descriptor;
    }

    public function setDescriptor(?string $descriptor = null): self
    {
        $this->descriptor = $descriptor;
        return $this;
    }

    public function getOdd(): ?bool
    {
        return $this->odd;
    }

    public function setOdd(bool $odd): self
    {
        $this->odd = $odd;
        return $this;
    }

    public function getEven(): ?bool
    {
        return $this->even;
    }

    public function setEven(bool $even): self
    {
        $this->even = $even;
        return $this;
    }

    public function jsonSerialize()
    {
        return $this->getSetProperties();
    }
}
