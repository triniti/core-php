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
    /** @var int */
    protected $columnIndex;

    /** @var string */
    protected $descriptor;

    /** @var bool */
    protected $odd;

    /** @var bool */
    protected $even;

    /**
     * @return int
     */
    public function getColumnIndex(): ?int
    {
        return $this->columnIndex;
    }

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
     * @return string
     */
    public function getDescriptor(): ?string
    {
        return $this->descriptor;
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
     * @return bool
     */
    public function getOdd(): ?bool
    {
        return $this->odd;
    }

    /**
     * @param bool $odd
     *
     * @return static
     */
    public function setOdd(bool $odd): self
    {
        $this->odd = $odd;
        return $this;
    }

    /**
     * @return bool
     */
    public function getEven(): ?bool
    {
        return $this->even;
    }

    /**
     * @param bool $even
     *
     * @return static
     */
    public function setEven(bool $even): self
    {
        $this->even = $even;
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
