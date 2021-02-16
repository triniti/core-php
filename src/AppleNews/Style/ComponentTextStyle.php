<?php
declare(strict_types=1);

namespace Triniti\AppleNews\Style;

use Assert\Assertion;

/**
 * @link https://developer.apple.com/documentation/apple_news/component_text_style
 */
class ComponentTextStyle extends TextStyle
{
    /** @var DropCapStyle */
    protected $dropCapStyle;

    /** @var int */
    protected $firstLineIndent;

    /** @var bool */
    protected $hangingPunctuation = false;

    /** @var bool */
    protected $hyphenation;

    /** @var int */
    protected $lineHeight;

    /** @var TextStyle */
    protected $linkStyle;

    /** @var int */
    protected $paragraphSpacingBefore;

    /** @var int */
    protected $paragraphSpacingAfter;

    /** @var string */
    protected $textAlignment;

    /** @var string */
    protected $textTransform;

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
     * @var array
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
     * @var string[] Valid vertical alignment values
     */
    private $validVerticalAlignment = [
        'superscript',
        'subscript',
        'baseline',
    ];

    /**
     * @var string[] Valid Text Alignment values
     */
    private $validTextAlignments = [
        'left',
        'right',
        'center',
        'justified',
        'none',
    ];

    /**
     * @var string[] Valid Text Transform values
     */
    private $validTextTransforms = [
        'uppercase',
        'lowercase',
        'capitalize',
        'none',
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
     * @return DropCapStyle
     */
    public function getDropCapStyle(): ?DropCapStyle
    {
        return $this->dropCapStyle;
    }

    /**
     * @param DropCapStyle $dropCapStyle
     *
     * @return static
     */
    public function setDropCapStyle(?DropCapStyle $dropCapStyle = null): self
    {
        if (null !== $dropCapStyle) {
            $dropCapStyle->validate();
        }

        $this->dropCapStyle = $dropCapStyle;
        return $this;
    }

    /**
     * @return int
     */
    public function getFirstLineIndent(): ?int
    {
        return $this->firstLineIndent;
    }

    /**
     * @param int $firstLineIndent
     *
     * @return static
     */
    public function setFirstLineIndent(?int $firstLineIndent = null): self
    {
        $this->firstLineIndent = $firstLineIndent;
        return $this;
    }

    /**
     * @param string $fontFamily
     *
     * @return static
     */
    public function setFontFamily(?string $fontFamily = null): self
    {
        $this->fontFamily = $fontFamily;
        return $this;
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
     * @param int $fontSize
     *
     * @return static
     */
    public function setFontSize(?int $fontSize = null): self
    {
        $this->fontSize = $fontSize;
        return $this;
    }

    /**
     * @param string $fontStyle
     *
     * @return static
     */
    public function setFontStyle(?string $fontStyle = 'normal'): ComponentTextStyle
    {
        if (null !== $fontStyle) {
            Assertion::inArray($fontStyle, $this->validFontStyles);
        }

        $this->fontStyle = $fontStyle;
        return $this;
    }

    /**
     * @param int|string $fontWeight
     *
     * @return static
     */
    public function setFontWeight($fontWeight = null): self
    {
        if (null !== $fontWeight) {
            Assertion::inArray($fontWeight, $this->validFontWeight);
        }

        $this->fontWeight = $fontWeight;
        return $this;
    }

    /**
     * @param string $fontWidth
     *
     * @return static
     */
    public function setFontWidth(?string $fontWidth = null): self
    {
        if (null !== $fontWidth) {
            Assertion::inArray($fontWidth, $this->validFontWidth);
        }

        $this->fontWidth = $fontWidth;
        return $this;
    }

    /**
     * @return bool
     */
    public function getHangingPunctuation(): bool
    {
        return $this->hangingPunctuation;
    }

    /**
     * @param bool $hangingPunctuation
     *
     * @return static
     */
    public function setHangingPunctuation(bool $hangingPunctuation = true): self
    {
        $this->hangingPunctuation = $hangingPunctuation;
        return $this;
    }

    /**
     * @return bool
     */
    public function getHyphenation(): ?bool
    {
        return $this->hyphenation;
    }

    /**
     * @param bool $hyphenation
     *
     * @return static
     */
    public function setHyphenation(bool $hyphenation = true): self
    {
        $this->hyphenation = $hyphenation;
        return $this;
    }

    /**
     * @return int
     */
    public function getLineHeight(): ?int
    {
        return $this->lineHeight;
    }

    /**
     * @param int $lineHeight
     *
     * @return static
     */
    public function setLineHeight(?int $lineHeight = null): self
    {
        $this->lineHeight = $lineHeight;
        return $this;
    }

    /**
     * @return TextStyle
     */
    public function getLinkStyle(): ?TextStyle
    {
        return $this->linkStyle;
    }

    /**
     * @param TextStyle $linkStyle
     *
     * @return static
     */
    public function setLinkStyle(?TextStyle $linkStyle = null): self
    {
        $this->linkStyle = $linkStyle;
        return $this;
    }

    /**
     * @param ListItemStyle $orderedListItems
     *
     * @return static
     */
    public function setOrderedListItems(?ListItemStyle $orderedListItems = null): self
    {
        if (null !== $orderedListItems) {
            $orderedListItems->validate();
        }

        $this->orderedListItems = $orderedListItems;
        return $this;
    }

    /**
     * @return int
     */
    public function getParagraphSpacingBefore(): ?int
    {
        return $this->paragraphSpacingBefore;
    }

    /**
     * @param int $paragraphSpacingBefore
     *
     * @return static
     */
    public function setParagraphSpacingBefore(?int $paragraphSpacingBefore = null): self
    {
        $this->paragraphSpacingBefore = $paragraphSpacingBefore;
        return $this;
    }

    /**
     * @return int
     */
    public function getParagraphSpacingAfter(): ?int
    {
        return $this->paragraphSpacingAfter;
    }

    /**
     * @param int $paragraphSpacingAfter
     *
     * @return static
     */
    public function setParagraphSpacingAfter(?int $paragraphSpacingAfter = null): self
    {
        $this->paragraphSpacingAfter = $paragraphSpacingAfter;
        return $this;
    }

    /**
     * @param bool|TextDecoration $strikethrough
     *
     * @return static
     */
    public function setStrikethrough($strikethrough = null): self
    {
        if ($strikethrough instanceof TextDecoration) {
            $strikethrough->validate();
        }

        $this->strikethrough = $strikethrough;
        return $this;
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
    public function getTextAlignment(): ?string
    {
        return $this->textAlignment;
    }

    /**
     * @param string $textAlignment
     *
     * @return static
     */
    public function setTextAlignment(string $textAlignment = 'none'): self
    {
        Assertion::inArray($textAlignment, $this->validTextAlignments);
        $this->textAlignment = $textAlignment;
        return $this;
    }

    /**
     * @param string $textColor
     *
     * @return static
     */
    public function setTextColor(?string $textColor = null): self
    {
        $this->textColor = $textColor;
        return $this;
    }

    /**
     * @param Shadow $shadow
     *
     * @return static
     */
    public function setTextShadow(?Shadow $shadow = null): self
    {
        $this->textShadow = $shadow;
        return $this;
    }

    /**
     * @param float $tracking
     *
     * @return static
     */
    public function setTracking(?float $tracking = null): self
    {
        $this->tracking = $tracking;
        return $this;
    }

    /**
     * @return string
     */
    public function getTextTransform(): ?string
    {
        return $this->textTransform;
    }

    /**
     * @param string $textTransform
     *
     * @return static
     */
    public function setTextTransform(string $textTransform = 'none'): self
    {
        Assertion::inArray($textTransform, $this->validTextTransforms);
        $this->textTransform = $textTransform;
        return $this;
    }

    /**
     * @param bool|TextDecoration $underline
     *
     * @return static
     */
    public function setUnderline($underline = null): self
    {
        if ($underline instanceof TextDecoration) {
            $underline->validate();
        }

        $this->underline = $underline;
        return $this;
    }

    /**
     * @param ListItemStyle $unorderedListItems
     *
     * @return static
     */
    public function setUnorderedListItems(?ListItemStyle $unorderedListItems = null): self
    {
        if (null !== $unorderedListItems) {
            $unorderedListItems->validate();
        }

        $this->unorderedListItems = $unorderedListItems;
        return $this;
    }

    /**
     * @param string $verticalAlignment
     *
     * @return static
     */
    public function setVerticalAlignment(string $verticalAlignment = 'baseline'): self
    {
        Assertion::inArray($verticalAlignment, $this->validVerticalAlignment);
        $this->verticalAlignment = $verticalAlignment;
        return $this;
    }
}
