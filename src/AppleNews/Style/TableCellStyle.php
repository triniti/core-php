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
    /** @var string */
    protected $backgroundColor;

    /** @var Border */
    protected $border;

    /** @var ConditionalTableCellStyle */
    protected $conditional;

    /** @var int|string */
    protected $height;

    /** @var string */
    protected $horizontalAlignment;

    /** int|string */
    protected $minimumWidth;

    /** @var int|string|Padding */
    protected $padding;

    /** @var ComponentTextStyle|string */
    protected $textStyle;

    /** @var string */
    protected $verticalAlignment;

    /** @var int */
    protected $width;

    private $validHorizontalAlignments = [
        'left',
        'center',
        'right',
    ];

    private $validVerticalAlignments = [
        'top',
        'center',
        'bottom',
    ];

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
     * @return Border
     */
    public function getBorder(): ?Border
    {
        return $this->border;
    }

    /**
     * @param Border $border
     *
     * @return static
     */
    public function setBorder(?Border $border = null): self
    {
        $this->border = $border;
        return $this;
    }

    /**
     * @return ConditionalTableCellStyle
     */
    public function getConditional(): ?ConditionalTableCellStyle
    {
        return $this->conditional;
    }

    /**
     * @param ConditionalTableCellStyle $conditional
     *
     * @return static
     */
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

    /**
     * @return string
     */
    public function getHorizontalAlignment(): ?string
    {
        return $this->horizontalAlignment;
    }

    /**
     * @param string $horizontalAlignment
     *
     * @return static
     */
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

    /**
     * @return string
     */
    public function getVerticalAlignment(): ?string
    {
        return $this->verticalAlignment;
    }

    /**
     * @param string $verticalAlignment
     *
     * @return static
     */
    public function setVerticalAlignment(string $verticalAlignment = 'center'): self
    {
        Assertion::inArray($verticalAlignment, $this->validVerticalAlignments);
        $this->verticalAlignment = $verticalAlignment;
        return $this;
    }

    /**
     * @return int
     */
    public function getWidth(): ?int
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
