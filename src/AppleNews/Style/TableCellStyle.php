<?php
declare(strict_types=1);

namespace Triniti\AppleNews\Style;

use Assert\Assertion;
use Triniti\AppleNews\AppleNewsObject;

/**
 * @link https://developer.apple.com/documentation/apple_news/table_cell_style
 */
class TableCellStyle extends AppleNewsObject
{
    protected ?string $backgroundColor = null;
    protected ?Border $border = null;
    protected ?ConditionalTableCellStyle $conditional = null;
    protected ?string $horizontalAlignment = null;
    protected ?string $verticalAlignment = null;
    protected ?int $width = null;

    /** @var int|string */
    protected $height = null;

    /** @var int|string */
    protected $minimumWidth = null;

    /** @var int|string|Padding */
    protected $padding = null;

    /** @var ComponentTextStyle|string */
    protected $textStyle = null;

    private array $validHorizontalAlignments = [
        'left',
        'center',
        'right',
    ];

    private array $validVerticalAlignments = [
        'top',
        'center',
        'bottom',
    ];

    public function getBackgroundColor(): ?string
    {
        return $this->backgroundColor;
    }

    public function setBackgroundColor(?string $backgroundColor = null): self
    {
        $this->backgroundColor = $backgroundColor;
        return $this;
    }

    public function getBorder(): ?Border
    {
        return $this->border;
    }

    public function setBorder(?Border $border = null): self
    {
        $this->border = $border;
        return $this;
    }

    public function getConditional(): ?ConditionalTableCellStyle
    {
        return $this->conditional;
    }

    public function setConditional(?ConditionalTableCellStyle $conditional = null): self
    {
        $this->conditional = $conditional;
        return $this;
    }

    /**
     * @return int|string
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * @param int|string $height
     *
     * @return static
     */
    public function setHeight($height = null): self
    {
        $this->height = $height;
        return $this;
    }

    public function getHorizontalAlignment(): ?string
    {
        return $this->horizontalAlignment;
    }

    public function setHorizontalAlignment(string $horizontalAlignment = 'left'): self
    {
        Assertion::inArray($horizontalAlignment, $this->validHorizontalAlignments);
        $this->horizontalAlignment = $horizontalAlignment;
        return $this;
    }

    /**
     * @return int|string
     */
    public function getMinimumWidth()
    {
        return $this->minimumWidth;
    }

    /**
     * @param int|string $minimumWidth
     *
     * @return static
     */
    public function setMinimumWidth($minimumWidth = null): self
    {
        $this->minimumWidth = $minimumWidth;
        return $this;
    }

    /**
     * @return int|string|Padding
     */
    public function getPadding()
    {
        return $this->padding;
    }

    /**
     * @param int|string|Padding $padding
     *
     * @return static
     */
    public function setPadding($padding = null): self
    {
        $this->padding = $padding;
        return $this;
    }

    /**
     * @return string|ComponentTextStyle
     */
    public function getTextStyle()
    {
        return $this->textStyle;
    }

    /**
     * @param string|ComponentTextStyle $textStyle
     *
     * @return static
     */
    public function setTextStyle($textStyle = null): self
    {
        $this->textStyle = $textStyle;
        return $this;
    }

    public function getVerticalAlignment(): ?string
    {
        return $this->verticalAlignment;
    }

    public function setVerticalAlignment(string $verticalAlignment = 'center'): self
    {
        Assertion::inArray($verticalAlignment, $this->validVerticalAlignments);
        $this->verticalAlignment = $verticalAlignment;
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
