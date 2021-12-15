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
    /** @var string */
    protected $descriptor;

    /** @var int */
    protected $rowIndex;

    /** @var bool */
    protected $odd;

    /** @var bool */
    protected $even;

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
     * @return int
     */
    public function getRowIndex(): ?int
    {
        return $this->rowIndex;
    }

    /**
     * @param int $rowIndex
     *
     * @return static
     */
    public function setRowIndex(int $rowIndex): self
    {
        Assertion::greaterOrEqualThan($rowIndex, 0);
        $this->rowIndex = $rowIndex;
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
    public function setOdd(?bool $odd = true): self
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
    public function setEven(?bool $even = true): self
    {
        $this->even = $even;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize(): array
    {
        return $this->getSetProperties();
    }
}
