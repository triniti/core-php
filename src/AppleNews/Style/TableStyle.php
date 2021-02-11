<?php
declare(strict_types=1);

namespace Triniti\AppleNews\Style;

use Triniti\AppleNews\AppleNewsObject;

/**
 * @link https://developer.apple.com/documentation/apple_news/table_style
 */
class TableStyle extends AppleNewsObject
{
    protected ?TableCellStyle $cell = null;
    protected ?TableColumnStyle $columns = null;
    protected ?TableCellStyle $headerCells = null;
    protected ?TableColumnStyle $headerColumns = null;
    protected ?TableRowStyle $headRows = null;
    protected ?TableRowStyle $rows = null;

    public function getCell(): ?TableCellStyle
    {
        return $this->cell;
    }

    public function setCell(?TableCellStyle $cell = null): self
    {
        $this->cell = $cell;
        return $this;
    }

    public function getColumns(): ?TableColumnStyle
    {
        return $this->columns;
    }

    public function setColumns(?TableColumnStyle $columns = null): self
    {
        $this->columns = $columns;
        return $this;
    }

    public function getHeaderCells(): ?TableCellStyle
    {
        return $this->headerCells;
    }

    public function setHeaderCells(?TableCellStyle $headerCells = null): self
    {
        $this->headerCells = $headerCells;
        return $this;
    }

    public function getHeaderColumns(): ?TableColumnStyle
    {
        return $this->headerColumns;
    }

    public function setHeaderColumns(?TableColumnStyle $headerColumns = null): self
    {
        $this->headerColumns = $headerColumns;
        return $this;
    }

    public function getHeadRows(): ?TableRowStyle
    {
        return $this->headRows;
    }

    public function setHeadRows(?TableRowStyle $headRows = null): self
    {
        $this->headRows = $headRows;
        return $this;
    }

    public function getRows(): ?TableRowStyle
    {
        return $this->rows;
    }

    public function setRows(?TableRowStyle $rows = null): self
    {
        $this->rows = $rows;
        return $this;
    }

    public function jsonSerialize()
    {
        return $this->getSetProperties();
    }
}
