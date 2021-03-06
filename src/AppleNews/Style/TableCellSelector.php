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
    /** @var int */
    protected $columnIndex;

    /** @var string */
    protected $descriptor;

    /** @var bool */
    protected $evenColumns;

    /** @var bool */
    protected $evenRows;

    /** @var bool */
    protected $oddColumns;

    /** @var bool */
    protected $oddRows;

    /** @var int */
    protected $rowIndex;

    /**
     * @param int $columnIndex
     *
     * @return static
     */
    public function setColumnIndex(int $columnIndex): self
    {
        Assertion::greaterOrEqualThan($columnIndex, 0);
        $this->columnIndex = $columnIndex;
        return $this;
    }

    /**
     * @param string $descriptor
     *
     * @return static
     */
    public function setDescriptor(?string $descriptor = null): self
    {
        $this->descriptor = $descriptor;
        return $this;
    }

    /**
     * @param bool $evenColumns
     *
     * @return static
     */
    public function setEvenColumns(bool $evenColumns): self
    {
        $this->evenColumns = $evenColumns;
        return $this;
    }

    /**
     * @param bool $evenRows
     *
     * @return static
     */
    public function setEvenRows(bool $evenRows): self
    {
        $this->evenRows = $evenRows;
        return $this;
    }

    /**
     * @param bool $oddColumns
     *
     * @return static
     */
    public function setOddColumns(bool $oddColumns): self
    {
        $this->oddColumns = $oddColumns;
        return $this;
    }

    /**
     * @param bool $oddRows
     *
     * @return static
     */
    public function setOddRows(bool $oddRows): self
    {
        $this->oddRows = $oddRows;
        return $this;
    }

    /**
     * @param int $rowIndex
     *
     * @return static
     */
    public function setRowIndex(int $rowIndex): self
    {
        Assertion::greaterOrEqualThan($rowIndex, 0, 'Row index starts at 0');
        $this->rowIndex = $rowIndex;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        return $this->getSetProperties();
    }
}
