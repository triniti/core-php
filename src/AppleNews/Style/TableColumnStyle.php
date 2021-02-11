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
    protected ?string $backgroundColor = null;
    protected ?ConditionalTableColumnStyle $conditional = null;
    protected ?StrokeStyle $divider = null;
    protected ?int $width = null;

    /** @var int|SupportedUnits */
    protected $minimumWidth = null;

    public function getBackgroundColor(): ?string
    {
        return $this->backgroundColor;
    }

    public function setBackgroundColor(?string $backgroundColor = null): self
    {
        $this->backgroundColor = $backgroundColor;
        return $this;
    }

    public function getConditional(): ?ConditionalTableColumnStyle
    {
        return $this->conditional;
    }

    public function setConditional(?ConditionalTableColumnStyle $conditional = null): self
    {
        $this->conditional = $conditional;
        return $this;
    }

    public function getDivider(): ?StrokeStyle
    {
        return $this->divider;
    }

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

    public function getWidth(): ?int
    {
        return $this->width;
    }

    public function setWidth(?int $width = null): self
    {
        $this->width = $width;
        return $this;
    }

    public function jsonSerialize()
    {
        return $this->getSetProperties();
    }
}
