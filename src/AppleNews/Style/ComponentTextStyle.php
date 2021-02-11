<?php
declare(strict_types=1);

namespace Triniti\AppleNews\Style;

use Assert\Assertion;

/**
 * @link https://developer.apple.com/documentation/apple_news/component_text_style
 */
class ComponentTextStyle extends TextStyle
{
    protected ?DropCapStyle $dropCapStyle = null;
    protected ?int $firstLineIndent = null;
    protected bool $hangingPunctuation = false;
    protected ?bool $hyphenation = null;
    protected ?int $lineHeight = null;
    protected ?TextStyle $linkStyle = null;
    protected ?int $paragraphSpacingBefore = null;
    protected ?int $paragraphSpacingAfter = null;
    protected ?string $textAlignment = null;
    protected ?string $textTransform = null;

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
     * @var string[] Valid vertical alignment values
     */
    private array $validVerticalAlignment = [
        'superscript',
        'subscript',
        'baseline',
    ];

    /**
     * @var string[] Valid Text Alignment values
     */
    private array $validTextAlignments = [
        'left',
        'right',
        'center',
        'justified',
        'none',
    ];

    /**
     * @var string[] Valid Text Transform values
     */
    private array $validTextTransforms = [
        'uppercase',
        'lowercase',
        'capitalize',
        'none',
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

    public function getDropCapStyle(): ?DropCapStyle
    {
        return $this->dropCapStyle;
    }

    public function setDropCapStyle(?DropCapStyle $dropCapStyle = null): self
    {
        if (null !== $dropCapStyle) {
            $dropCapStyle->validate();
        }

        $this->dropCapStyle = $dropCapStyle;
        return $this;
    }

    public function getFirstLineIndent(): ?int
    {
        return $this->firstLineIndent;
    }

    public function setFirstLineIndent(?int $firstLineIndent = null): self
    {
        $this->firstLineIndent = $firstLineIndent;
        return $this;
    }

    public function setFontFamily(?string $fontFamily = null): self
    {
        $this->fontFamily = $fontFamily;
        return $this;
    }

    public function setFontName(?string $fontName = null)
    {
        $this->fontName = $fontName;
        return $this;
    }

    public function setFontSize(?int $fontSize = null): self
    {
        $this->fontSize = $fontSize;
        return $this;
    }

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

    public function setFontWidth(?string $fontWidth = null): self
    {
        if (null !== $fontWidth) {
            Assertion::inArray($fontWidth, $this->validFontWidth);
        }

        $this->fontWidth = $fontWidth;
        return $this;
    }

    public function getHangingPunctuation(): bool
    {
        return $this->hangingPunctuation;
    }

    public function setHangingPunctuation(bool $hangingPunctuation = true): self
    {
        $this->hangingPunctuation = $hangingPunctuation;
        return $this;
    }

    public function getHyphenation(): ?bool
    {
        return $this->hyphenation;
    }

    public function setHyphenation(bool $hyphenation = true): self
    {
        $this->hyphenation = $hyphenation;
        return $this;
    }

    public function getLineHeight(): ?int
    {
        return $this->lineHeight;
    }

    public function setLineHeight(?int $lineHeight = null): self
    {
        $this->lineHeight = $lineHeight;
        return $this;
    }

    public function getLinkStyle(): ?TextStyle
    {
        return $this->linkStyle;
    }

    public function setLinkStyle(?TextStyle $linkStyle = null): self
    {
        $this->linkStyle = $linkStyle;
        return $this;
    }

    public function setOrderedListItems(?ListItemStyle $orderedListItems = null): self
    {
        if (null !== $orderedListItems) {
            $orderedListItems->validate();
        }

        $this->orderedListItems = $orderedListItems;
        return $this;
    }

    public function getParagraphSpacingBefore(): ?int
    {
        return $this->paragraphSpacingBefore;
    }

    public function setParagraphSpacingBefore(?int $paragraphSpacingBefore = null): self
    {
        $this->paragraphSpacingBefore = $paragraphSpacingBefore;
        return $this;
    }

    public function getParagraphSpacingAfter(): ?int
    {
        return $this->paragraphSpacingAfter;
    }

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

    public function setStroke(?TextStrokeStyle $stroke = null)
    {
        $this->stroke = $stroke;
        return $this;
    }

    public function getTextAlignment(): ?string
    {
        return $this->textAlignment;
    }

    public function setTextAlignment(string $textAlignment = 'none'): self
    {
        Assertion::inArray($textAlignment, $this->validTextAlignments);
        $this->textAlignment = $textAlignment;
        return $this;
    }

    public function setTextColor(?string $textColor = null): self
    {
        $this->textColor = $textColor;
        return $this;
    }

    public function setTextShadow(?Shadow $shadow = null): self
    {
        $this->textShadow = $shadow;
        return $this;
    }

    public function setTracking(?float $tracking = null): self
    {
        $this->tracking = $tracking;
        return $this;
    }

    public function getTextTransform(): ?string
    {
        return $this->textTransform;
    }

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

    public function setUnorderedListItems(?ListItemStyle $unorderedListItems = null): self
    {
        if (null !== $unorderedListItems) {
            $unorderedListItems->validate();
        }

        $this->unorderedListItems = $unorderedListItems;
        return $this;
    }

    public function setVerticalAlignment(string $verticalAlignment = 'baseline'): self
    {
        Assertion::inArray($verticalAlignment, $this->validVerticalAlignment);
        $this->verticalAlignment = $verticalAlignment;
        return $this;
    }
}
