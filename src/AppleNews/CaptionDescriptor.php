<?php
declare(strict_types=1);

namespace Triniti\AppleNews;

use Assert\Assertion;
use Triniti\AppleNews\Link\Addition;
use Triniti\AppleNews\Style\ComponentTextStyle;
use Triniti\AppleNews\Style\InlineTextStyle;

/**
 * @link https://developer.apple.com/documentation/apple_news/caption_descriptor
 */
class CaptionDescriptor extends AppleNewsObject
{
    protected ?string $format = null;
    protected ?string $text = null;

    /** @var Addition[] */
    protected array $additions = [];

    /** @var InlineTextStyle[] */
    protected array $inlineTextStyles = [];

    /** @var string|ComponentTextStyle */
    protected $textStyle;

    /**
     * @return Addition[]
     */
    public function getAdditions(): array
    {
        return $this->additions;
    }

    public function addAddition(?Addition $addition = null): self
    {
        if (null === $addition) {
            return $this;
        }

        $addition->validate();
        $this->additions[] = $addition;
        return $this;
    }

    public function addAdditions(?array $additions = []): self
    {
        foreach ($additions as $addition) {
            $this->addAddition($addition);
        }

        return $this;
    }

    public function setAdditions(?array $additions = []): self
    {
        $this->additions = [];
        $this->addAdditions($additions);
        return $this;
    }

    public function getFormat(): ?string
    {
        return $this->format;
    }

    public function setFormat(string $format): self
    {
        Assertion::inArray($format, ['markdown', 'html', 'none']);
        $this->format = $format;
        return $this;
    }

    /**
     * @return InlineTextStyle[]
     */
    public function getInlineTextStyles(): array
    {
        return $this->inlineTextStyles;
    }

    public function addInlineTextStyle(?InlineTextStyle $inlineTextStyle = null): self
    {
        if (null === $inlineTextStyle) {
            return $this;
        }

        $inlineTextStyle->validate();
        $this->inlineTextStyles[] = $inlineTextStyle;
        return $this;
    }

    public function addInlineTextStyles(?array $inlineTextStyles = []): self
    {
        foreach ($inlineTextStyles as $inlineTextStyle) {
            $this->addInlineTextStyle($inlineTextStyle);
        }

        return $this;
    }

    public function setInlineTextStyles(?array $inlineTextStyles = []): self
    {
        $this->inlineTextStyles = [];
        $this->addInlineTextStyles($inlineTextStyles);
        return $this;
    }

    public function getText(): ?string
    {
        return $this->text;
    }

    public function setText(string $text): self
    {
        $this->text = $text;
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
        if (!is_string($textStyle) && (null !== $textStyle) && !($textStyle instanceof ComponentTextStyle)) {
            Assertion::true(
                false,
                'textStyle must be a string or instance of ComponentTextStyle.'
            );
        }

        if ($textStyle instanceof ComponentTextStyle) {
            $textStyle->validate();
        }

        $this->textStyle = $textStyle;
        return $this;
    }

    public function jsonSerialize()
    {
        return $this->getSetProperties();
    }

    public function validate(): void
    {
        Assertion::notNull($this->text);
    }
}