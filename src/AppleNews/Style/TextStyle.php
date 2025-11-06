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
    /** @var ConditionalTextStyle[] */
    protected $conditional;

    /** @var string */
    protected $backgroundColor;

    /** @var string */
    protected $fontFamily;

    /** @var string */
    protected $fontName;

    /** @var int */
    protected $fontSize;

    /** @var string */
    protected $fontStyle;

    /** @var int|string */
    protected $fontWeight;

    /** @var string */
    protected $fontWidth;

    /** @var ListItemStyle */
    protected $orderedListItems;

    /** @var bool|TextDecoration */
    protected $strikethrough;

    /** @var TextStrokeStyle */
    protected $stroke;

    /** @var string */
    protected $textColor;

    /** @var Shadow */
    protected $textShadow;

    /** @var float */
    protected $tracking;

    /** @var bool|TextDecoration */
    protected $underline;

    /** @var ListItemStyle */
    protected $unorderedListItems;

    /** @var string */
    protected $verticalAlignment;

    /**
     * @var array
     */
    private $validFontStyles = [
        'normal',
        'italic',
        'oblique',
    ];

    /**
     * @var array
     */
    private $validFontWeight = [
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
    private $validFontWidth = [
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
    private $validVerticalAlignment = [
        'superscript',
        'subscript',
        'baseline',
    ];

    /**
     * @return ConditionalTextStyle[]
     */
    public function getConditional(): array
    {
        return $this->conditional;
    }

    /**
     * @param ConditionalTextStyle[] $conditionals
     *
     * @return static
     * @throws \Throwable
     */
    public function setConditional(?array $conditionals = []): self
    {
        $this->conditional = [];

        if (!empty($conditionals)) {
            foreach ($conditionals as $conditional) {
                $this->addConditional($conditional);
            }
        }

        return $this;
    }

    /**
     * @param ConditionalTextStyle|null $conditional
     *
     * @return static
     * @throws \Throwable
     */
    public function addConditional(?ConditionalTextStyle $conditional = null): self
    {
        if (null !== $conditional) {
            $conditional->validate();
            $this->conditional[] = $conditional;
        }

        return $this;
    }

    /**
     * @param ConditionalTextStyle[] $conditionals
     *
     * @return static
     * @throws \Throwable
     */
    public function addConditionals(?array $conditionals = []): self
    {
        if (!empty($conditionals)) {
            foreach ($conditionals as $conditional) {
                $this->addConditional($conditional);
            }
        }

        return $this;
    }

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
    public function setBackgroundColor(?string $backgroundColor = null)
    {
        $this->backgroundColor = $backgroundColor;
        return $this;
    }

    /**
     * @return string
     */
    public function getFontFamily(): ?string
    {
        return $this->fontFamily;
    }

    /**
     * @param string $fontFamily
     *
     * @return static
     */
    public function setFontFamily(?string $fontFamily = null)
    {
        $this->fontFamily = $fontFamily;
        return $this;
    }

    /**
     * @return string
     */
    public function getFontName(): ?string
    {
        return $this->fontName;
    }

    /**
     * @param string $fontName
     *
     * @return static
     */
    public function setFontName(?string $fontName = null)
    {
        $this->fontName = $fontName;
        return $this;
    }

    /**
     * @return int
     */
    public function getFontSize(): ?int
    {
        return $this->fontSize;
    }

    /**
     * @param int $fontSize
     *
     * @return static
     */
    public function setFontSize(?int $fontSize = null)
    {
        $this->fontSize = $fontSize;
        return $this;
    }

    /**
     * @return string
     */
    public function getFontStyle(): ?string
    {
        return $this->fontStyle;
    }

    /**
     * @param string $fontStyle
     *
     * @return static
     */
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

    /**
     * @return string
     */
    public function getFontWidth(): ?string
    {
        return $this->fontWidth;
    }

    /**
     * @param string $fontWidth
     *
     * @return static
     */
    public function setFontWidth(string $fontWidth)
    {
        Assertion::inArray($fontWidth, $this->validFontWidth);
        $this->fontWidth = $fontWidth;
        return $this;
    }

    /**
     * @return ListItemStyle
     */
    public function getOrderedListItems(): ?ListItemStyle
    {
        return $this->orderedListItems;
    }

    /**
     * @param ListItemStyle $orderedListItems
     *
     * @return static
     */
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

    /**
     * @return TextStrokeStyle
     */
    public function getStroke(): ?TextStrokeStyle
    {
        return $this->stroke;
    }

    /**
     * @param TextStrokeStyle $stroke
     *
     * @return static
     */
    public function setStroke(?TextStrokeStyle $stroke = null)
    {
        $this->stroke = $stroke;
        return $this;
    }

    /**
     * @return string
     */
    public function getTextColor(): ?string
    {
        return $this->textColor;
    }

    /**
     * @param string $textColor
     *
     * @return static
     */
    public function setTextColor(?string $textColor = null)
    {
        $this->textColor = $textColor;
        return $this;
    }

    /**
     * @return Shadow
     */
    public function getTextShadow(): ?Shadow
    {
        return $this->textShadow;
    }

    /**
     * @param Shadow $shadow
     *
     * @return static
     */
    public function setTextShadow(?Shadow $shadow = null)
    {
        $this->textShadow = $shadow;
        return $this;
    }

    /**
     * @return float
     */
    public function getTracking(): ?float
    {
        return $this->tracking;
    }

    /**
     * @param float $tracking
     *
     * @return static
     */
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

    /**
     * @return ListItemStyle
     */
    public function getUnorderedListItems(): ?ListItemStyle
    {
        return $this->unorderedListItems;
    }

    /**
     * @param ListItemStyle $unorderedListItems
     *
     * @return static
     */
    public function setUnorderedListItems(?ListItemStyle $unorderedListItems = null)
    {
        if (null !== $unorderedListItems) {
            $unorderedListItems->validate();
        }

        $this->unorderedListItems = $unorderedListItems;
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
    public function setVerticalAlignment(string $verticalAlignment = 'baseline')
    {
        Assertion::inArray($verticalAlignment, $this->validVerticalAlignment);
        $this->verticalAlignment = $verticalAlignment;
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
