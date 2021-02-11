<?php
declare(strict_types=1);

namespace Triniti\AppleNews\Style;

use Assert\Assertion;
use Triniti\AppleNews\AppleNewsObject;

/**
 * @link https://developer.apple.com/documentation/apple_news/table_cell_selector
 */
class TableCellSelector extends AppleNewsObject
{
    protected ?int $columnIndex = null;
    protected ?string $descriptor = null;
    protected ?bool $evenColumns = null;
    protected ?bool $evenRows = null;
    protected ?bool $oddColumns = null;
    protected ?bool $oddRows = null;
    protected ?int $rowIndex = null;

    public function setColumnIndex(int $columnIndex): self
    {
        Assertion::greaterOrEqualThan($columnIndex, 0);
        $this->columnIndex = $columnIndex;
        return $this;
    }

    public function setDescriptor(?string $descriptor = null): self
    {
        $this->descriptor = $descriptor;
        return $this;
    }

    public function setEvenColumns(bool $evenColumns): self
    {
        $this->evenColumns = $evenColumns;
        return $this;
    }

    public function setEvenRows(bool $evenRows): self
    {
        $this->evenRows = $evenRows;
        return $this;
    }

    public function setOddColumns(bool $oddColumns): self
    {
        $this->oddColumns = $oddColumns;
        return $this;
    }

    public function setOddRows(bool $oddRows): self
    {
        $this->oddRows = $oddRows;
        return $this;
    }

    public function setRowIndex(int $rowIndex): self
    {
        Assertion::greaterOrEqualThan($rowIndex, 0, 'Row index starts at 0');
        $this->rowIndex = $rowIndex;
        return $this;
    }

    public function jsonSerialize()
    {
        return $this->getSetProperties();
    }
}
