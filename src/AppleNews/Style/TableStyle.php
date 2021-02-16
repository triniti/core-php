<?php
declare(strict_types=1);

namespace Triniti\AppleNews\Style;

use Triniti\AppleNews\AppleNewsObject;

/**
 * @link https://developer.apple.com/documentation/apple_news/table_style
 */
class TableStyle extends AppleNewsObject
{
    /** @var TableCellStyle */
    protected $cell;

    /** @var TableColumnStyle */
    protected $columns;

    /** @var TableCellStyle */
    protected $headerCells;

    /** @var TableColumnStyle */
    protected $headerColumns;

    /** @var TableRowStyle */
    protected $headRows;

    /** @var TableRowStyle */
    protected $rows;

    /**
     * @return TableCellStyle
     */
    public function getCell(): ?TableCellStyle
    {
        return $this->cell;
    }

    /**
     * @param TableCellStyle $cell
     *
     * @return static
     */
    public function setCell(?TableCellStyle $cell = null): self
    {
        $this->cell = $cell;
        return $this;
    }

    /**
     * @return TableColumnStyle
     */
    public function getColumns(): ?TableColumnStyle
    {
        return $this->columns;
    }

    /**
     * @param TableColumnStyle $columns
     *
     * @return static
     */
    public function setColumns(?TableColumnStyle $columns = null): self
    {
        $this->columns = $columns;
        return $this;
    }

    /**
     * @return TableCellStyle
     */
    public function getHeaderCells(): ?TableCellStyle
    {
        return $this->headerCells;
    }

    /**
     * @param TableCellStyle $headerCells
     *
     * @return static
     */
    public function setHeaderCells(?TableCellStyle $headerCells = null): self
    {
        $this->headerCells = $headerCells;
        return $this;
    }

    /**
     * @return TableColumnStyle
     */
    public function getHeaderColumns(): ?TableColumnStyle
    {
        return $this->headerColumns;
    }

    /**
     * @param TableColumnStyle $headerColumns
     *
     * @return static
     */
    public function setHeaderColumns(?TableColumnStyle $headerColumns = null): self
    {
        $this->headerColumns = $headerColumns;
        return $this;
    }

    /**
     * @return TableRowStyle
     */
    public function getHeadRows(): ?TableRowStyle
    {
        return $this->headRows;
    }

    /**
     * @param TableRowStyle $headRows
     *
     * @return static
     */
    public function setHeadRows(?TableRowStyle $headRows = null): self
    {
        $this->headRows = $headRows;
        return $this;
    }

    /**
     * @return TableRowStyle
     */
    public function getRows(): ?TableRowStyle
    {
        return $this->rows;
    }

    /**
     * @param TableRowStyle $rows
     *
     * @return static
     */
    public function setRows(?TableRowStyle $rows = null): self
    {
        $this->rows = $rows;
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
