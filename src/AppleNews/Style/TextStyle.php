<?php
declare(strict_types=1);

namespace Triniti\AppleNews\Style;

use Assert\Assertion;
use Triniti\AppleNews\AppleNewsObject;

/**
 * @link https://developer.apple.com/documentation/apple_news/text_style
 */
class TextStyle extends AppleNewsObject
{
    protected ?string $backgroundColor = null;
    protected ?string $fontFamily = null;
    protected ?string $fontName = null;
    protected ?int $fontSize = null;
    protected ?string $fontStyle = null;
    protected ?string $fontWidth = null;
    protected ?ListItemStyle $orderedListItems = null;
    protected ?TextStrokeStyle $stroke = null;
    protected ?string $textColor = null;
    protected ?Shadow $textShadow = null;
    protected ?float $tracking = null;
    protected ?ListItemStyle $unorderedListItems = null;
    protected ?string $verticalAlignment = null;

    /** @var int|string */
    protected $fontWeight;

    /** @var bool|TextDecoration */
    protected $strikethrough;

    /** @var bool|TextDecoration */
    protected $underline;

    private array $validFontStyles = [
        'normal',
        'italic',
        'oblique',
    ];

    private array $validFontWeight = [
        100,
        200,
        300,
        400,
        500,
        600,
        700,
        800,
        900,
        'thin',
        'extra-light',
        'extralight',
        'ultra-light',
        'light',
        'regular',
        'normal',
        'book',
        'roman',
        'medium',
        'semi-bold',
        'semibold',
        'demi-bold',
        'demibold',
        'bold',
        'extra-bold',
        'extrabold',
        'ultra-bold',
        'ultrabold',
        'black',
        'heavy',
        'lighter',
        'bolder',
    ];

    /**
     * @var string[]
     */
    private array $validFontWidth = [
        'ultra-condensed',
        'extra-condensed',
        'condensed',
        'semi-condensed',
        'normal',
        'semi-expanded',
        'expanded',
        'extra-expanded',
        'ultra-expande',
    ];

    /**
     * Valid vertical alignment values
     *
     * @var string[]
     */
    private array $validVerticalAlignment = [
        'superscript',
        'subscript',
        'baseline',
    ];

    public function getBackgroundColor(): ?string
    {
        return $this->backgroundColor;
    }

    public function setBackgroundColor(?string $backgroundColor = null)
    {
        $this->backgroundColor = $backgroundColor;
        return $this;
    }

    public function getFontFamily(): ?string
    {
        return $this->fontFamily;
    }

    public function setFontFamily(?string $fontFamily = null)
    {
        $this->fontFamily = $fontFamily;
        return $this;
    }

    public function getFontName(): ?string
    {
        return $this->fontName;
    }

    public function setFontName(?string $fontName = null)
    {
        $this->fontName = $fontName;
        return $this;
    }

    public function getFontSize(): ?int
    {
        return $this->fontSize;
    }

    public function setFontSize(?int $fontSize = null)
    {
        $this->fontSize = $fontSize;
        return $this;
    }

    public function getFontStyle(): ?string
    {
        return $this->fontStyle;
    }

    public function setFontStyle(string $fontStyle = 'normal')
    {
        Assertion::inArray($fontStyle, $this->validFontStyles);
        $this->fontStyle = $fontStyle;
        return $this;
    }

    /**
     * @return int|string
     */
    public function getFontWeight()
    {
        return $this->fontWeight;
    }

    /**
     * @param int|string $fontWeight
     *
     * @return static
     */
    public function setFontWeight($fontWeight)
    {
        Assertion::inArray($fontWeight, $this->validFontWeight);
        $this->fontWeight = $fontWeight;
        return $this;
    }

    public function getFontWidth(): ?string
    {
        return $this->fontWidth;
    }

    public function setFontWidth(string $fontWidth)
    {
        Assertion::inArray($fontWidth, $this->validFontWidth);
        $this->fontWidth = $fontWidth;
        return $this;
    }

    public function getOrderedListItems(): ?ListItemStyle
    {
        return $this->orderedListItems;
    }

    public function setOrderedListItems(?ListItemStyle $orderedListItems = null)
    {
        if (null === $orderedListItems) {
            $orderedListItems->validate();
        }

        $this->orderedListItems = $orderedListItems;
        return $this;
    }

    /**
     * @return bool|TextDecoration
     */
    public function getStrikethrough()
    {
        return $this->strikethrough;
    }

    /**
     * @param bool|TextDecoration $strikethrough
     *
     * @return static
     */
    public function setStrikethrough($strikethrough)
    {
        if ($strikethrough instanceof TextDecoration) {
            $strikethrough->validate();
        }

        $this->strikethrough = $strikethrough;
        return $this;
    }

    public function getStroke(): ?TextStrokeStyle
    {
        return $this->stroke;
    }

    public function setStroke(?TextStrokeStyle $stroke = null)
    {
        $this->stroke = $stroke;
        return $this;
    }

    public function getTextColor(): ?string
    {
        return $this->textColor;
    }

    public function setTextColor(?string $textColor = null)
    {
        $this->textColor = $textColor;
        return $this;
    }

    public function getTextShadow(): ?Shadow
    {
        return $this->textShadow;
    }

    public function setTextShadow(?Shadow $shadow = null)
    {
        $this->textShadow = $shadow;
        return $this;
    }

    public function getTracking(): ?float
    {
        return $this->tracking;
    }

    public function setTracking(?float $tracking = null)
    {
        $this->tracking = $tracking;
        return $this;
    }

    /**
     * @return bool|TextDecoration
     */
    public function getUnderline()
    {
        return $this->underline;
    }

    /**
     * @param TextDecoration|boolean $underline
     *
     * @return static
     */
    public function setUnderline($underline)
    {
        if ($underline instanceof TextDecoration) {
            $underline->validate();
        }

        $this->underline = $underline;
        return $this;
    }

    public function getUnorderedListItems(): ?ListItemStyle
    {
        return $this->unorderedListItems;
    }

    public function setUnorderedListItems(?ListItemStyle $unorderedListItems = null)
    {
        if (null !== $unorderedListItems) {
            $unorderedListItems->validate();
        }

        $this->unorderedListItems = $unorderedListItems;
        return $this;
    }

    public function getVerticalAlignment(): ?string
    {
        return $this->verticalAlignment;
    }

    public function setVerticalAlignment(string $verticalAlignment = 'baseline')
    {
        Assertion::inArray($verticalAlignment, $this->validVerticalAlignment);
        $this->verticalAlignment = $verticalAlignment;
        return $this;
    }

    public function jsonSerialize()
    {
        return $this->getSetProperties();
    }
}
