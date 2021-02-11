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
    protected ?string $backgroundColor = null;
    protected ?ConditionalTableRowStyle $conditional = null;
    protected ?StrokeStyle $divider = null;

    /** @var int|SupportedUnits */
    protected $height;

    public function getBackgroundColor(): ?string
    {
        return $this->backgroundColor;
    }

    public function setBackgroundColor(?string $backgroundColor = null): self
    {
        $this->backgroundColor = $backgroundColor;
        return $this;
    }

    public function getConditional(): ?ConditionalTableRowStyle
    {
        return $this->conditional;
    }

    public function setConditional(?ConditionalTableRowStyle $conditional = null): self
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

    public function jsonSerialize()
    {
        return $this->getSetProperties();
    }
}
