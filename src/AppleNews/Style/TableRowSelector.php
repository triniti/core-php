<?php
declare(strict_types=1);

namespace Triniti\AppleNews\Style;

use Assert\Assertion;
use Triniti\AppleNews\AppleNewsObject;

/**
 * @link https://developer.apple.com/documentation/apple_news/table_row_selector
 */
class TableRowSelector extends AppleNewsObject
{
    protected ?string $descriptor = null;
    protected ?int $rowIndex = null;
    protected ?bool $odd = null;
    protected ?bool $even = null;

    public function getDescriptor(): ?string
    {
        return $this->descriptor;
    }

    public function setDescriptor(?string $descriptor = null): self
    {
        $this->descriptor = $descriptor;
        return $this;
    }

    public function getRowIndex(): ?int
    {
        return $this->rowIndex;
    }

    public function setRowIndex(int $rowIndex): self
    {
        Assertion::greaterOrEqualThan($rowIndex, 0);
        $this->rowIndex = $rowIndex;
        return $this;
    }

    public function getOdd(): ?bool
    {
        return $this->odd;
    }

    public function setOdd(?bool $odd = true): self
    {
        $this->odd = $odd;
        return $this;
    }

    public function getEven(): ?bool
    {
        return $this->even;
    }

    public function setEven(?bool $even = true): self
    {
        $this->even = $even;
        return $this;
    }

    public function jsonSerialize()
    {
        return $this->getSetProperties();
    }
}
