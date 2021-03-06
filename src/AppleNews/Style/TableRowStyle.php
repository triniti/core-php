<?php
declare(strict_types=1);

namespace Triniti\AppleNews\Style;

use Triniti\AppleNews\AppleNewsObject;
use Triniti\AppleNews\SupportedUnits;

/**
 * @link https://developer.apple.com/documentation/apple_news/table_row_style
 */
class TableRowStyle extends AppleNewsObject
{
    /** @var string */
    protected $backgroundColor;

    /** @var  ConditionalTableRowStyle */
    protected $conditional;

    /** @var StrokeStyle */
    protected $divider;

    /** @var int|SupportedUnits */
    protected $height;

    /**
     * @return string
     */
    public function getBackgroundColor(): ?string
    {
        return $this->backgroundColor;
    }

    /**
     * @param string $backgroundColor
     *
     * @return static
     */
    public function setBackgroundColor(?string $backgroundColor = null): self
    {
        $this->backgroundColor = $backgroundColor;
        return $this;
    }

    /**
     * @return ConditionalTableRowStyle
     */
    public function getConditional(): ?ConditionalTableRowStyle
    {
        return $this->conditional;
    }

    /**
     * @param ConditionalTableRowStyle $conditional
     *
     * @return static
     */
    public function setConditional(?ConditionalTableRowStyle $conditional = null): self
    {
        $this->conditional = $conditional;
        return $this;
    }

    /**
     * @return StrokeStyle|null
     */
    public function getDivider(): ?StrokeStyle
    {
        return $this->divider;
    }

    /**
     * @param StrokeStyle $divider
     *
     * @return static
     */
    public function setDivider(?StrokeStyle $divider = null): self
    {
        $this->divider = $divider;
        return $this;
    }

    /**
     * @return int|SupportedUnits
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * @param int|SupportedUnits $height
     *
     * @return static
     */
    public function setHeight($height = null): self
    {
        $this->height = $height;
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
