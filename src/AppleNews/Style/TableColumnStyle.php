<?php
declare(strict_types=1);

namespace Triniti\AppleNews\Style;

use Triniti\AppleNews\AppleNewsObject;
use Triniti\AppleNews\SupportedUnits;

/**
 * @link https://developer.apple.com/documentation/apple_news/table_column_style
 */
class TableColumnStyle extends AppleNewsObject
{
    /** @var string */
    protected $backgroundColor;

    /** @var ConditionalTableColumnStyle */
    protected $conditional;

    /** @var StrokeStyle */
    protected $divider;

    /** @var int|SupportedUnits */
    protected $minimumWidth;

    /** @var int */
    protected $width;

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
     * @return ConditionalTableColumnStyle
     */
    public function getConditional(): ?ConditionalTableColumnStyle
    {
        return $this->conditional;
    }

    /**
     * @param ConditionalTableColumnStyle $conditional
     *
     * @return static
     */
    public function setConditional(?ConditionalTableColumnStyle $conditional = null): self
    {
        $this->conditional = $conditional;
        return $this;
    }

    /**
     * @return StrokeStyle
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
    public function getMinimumWidth()
    {
        return $this->minimumWidth;
    }

    /**
     * @param int|SupportedUnits $minimumWidth
     *
     * @return static
     */
    public function setMinimumWidth($minimumWidth = null): self
    {
        $this->minimumWidth = $minimumWidth;
        return $this;
    }

    /**
     * @return int
     */
    public function getWidth(): int
    {
        return $this->width;
    }

    /**
     * @param int $width
     *
     * @return static
     */
    public function setWidth(?int $width = null): self
    {
        $this->width = $width;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize(): mixed
    {
        return $this->getSetProperties();
    }
}
