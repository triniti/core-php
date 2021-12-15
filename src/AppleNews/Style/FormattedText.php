<?php
declare(strict_types=1);

namespace Triniti\AppleNews\Style;

use Assert\Assertion;
use Triniti\AppleNews\AppleNewsObject;
use Triniti\AppleNews\Link\Addition;

/**
 * @link https://developer.apple.com/documentation/apple_news/formatted_text
 */
class FormattedText extends AppleNewsObject
{
    /** @var Addition[] */
    protected $additions = [];

    /** @var string */
    protected $format;

    /** @var InlineTextStyle[] */
    protected $inlineTextStyles = [];

    /** @var string */
    protected $text;

    /** @var ComponentTextStyle|string */
    protected $textStyle;

    /** @var string[] */
    private $validFormats = [
        'html',
        'none',
    ];

    /**
     * @return Addition[]
     */
    public function getAdditions(): ?array
    {
        return $this->additions;
    }

    /**
     * @param Addition $addition
     *
     * @return static
     */

    public function addAddition(?Addition $addition = null): self
    {
        if (null === $addition) {
            return $this;
        }

        $addition->validate();
        $this->additions[] = $addition;
        return $this;
    }

    /**
     * @param Addition[] $additions
     *
     * @return static
     */
    public function addAdditions(?array $additions = []): self
    {
        foreach ($additions as $addition) {
            $this->addAddition($addition);
        }

        return $this;
    }

    /**
     * @param Addition[] $additions
     *
     * @return static
     */
    public function setAdditions(array $additions = []): self
    {
        $this->additions = [];
        $this->addAdditions($additions);
        return $this;
    }

    /**
     * @return string
     */
    public function getFormat(): ?string
    {
        return $this->format;
    }

    /**
     * @param string $format
     *
     * @return static
     */
    public function setFormat(?string $format = 'none'): self
    {
        if (null === $format) {
            $this->format = 'none';
        }

        Assertion::inArray($format, $this->validFormats);
        $this->format = $format;
        return $this;
    }

    /**
     * @return InlineTextStyle[]
     */
    public function getInlineTextStyles(): ?array
    {
        return $this->inlineTextStyles;
    }

    /**
     * @param InlineTextStyle $inlineTextStyles
     *
     * @return static
     */
    public function addInlineTextStyle(?InlineTextStyle $inlineTextStyles = null): self
    {
        if (null === $inlineTextStyles) {
            return $this;
        }

        $inlineTextStyles->validate();
        $this->inlineTextStyles[] = $inlineTextStyles;
        return $this;
    }

    /**
     * @param InlineTextStyle[] $inlineTextStyles
     *
     * @return static
     */
    public function addInlineTextStyles(array $inlineTextStyles = []): self
    {
        foreach ($inlineTextStyles as $inlineTextStyle) {
            $this->addInlineTextStyle($inlineTextStyle);
        }

        return $this;
    }

    /**
     * @param InlineTextStyle[] $inlineTextStyles
     *
     * @return static
     */
    public function setInlineTextStyles(array $inlineTextStyles = []): self
    {
        $this->inlineTextStyles = [];
        $this->addInlineTextStyles($inlineTextStyles);

        return $this;
    }

    /**
     * @return string
     */
    public function getText(): ?string
    {
        return $this->text;
    }

    /**
     * @param string $text
     *
     * @return static
     */
    public function setText(string $text): self
    {
        $this->text = $text;
        return $this;
    }

    /**
     * @return ComponentTextStyle|string
     */
    public function getTextStyle()
    {
        return $this->textStyle;
    }

    /**
     * @param ComponentTextStyle|string $textStyle
     *
     * @return static
     */
    public function setTextStyle($textStyle = null): self
    {
        if ($textStyle instanceof ComponentTextStyle) {
            $textStyle->validate();
        }

        $this->textStyle = $textStyle;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function validate(): void
    {
        Assertion::notNull($this->text, 'Text is required.');
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize(): mixed
    {
        $properties = $this->getSetProperties();
        $properties['type'] = 'formatted_text';
        return $properties;
    }
}
