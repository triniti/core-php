<?php
declare(strict_types=1);

namespace Triniti\AppleNews\Component;

use Assert\Assertion;
use Triniti\AppleNews\Link\Addition;
use Triniti\AppleNews\Style\ComponentTextStyle;
use Triniti\AppleNews\Style\InlineTextStyle;

/**
 * @link https://developer.apple.com/documentation/apple_news/text
 */
abstract class Text extends Component
{
    /** @var string */
    protected $text;

    /** @var Addition[] */
    protected $additions = [];

    /** @var string */
    protected $format;

    /** @var InlineTextStyle[] */
    protected $inlineTextStyles = [];

    /** @var string|ComponentTextStyle */
    protected $textStyle;

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
    public function setText(?string $text = null): self
    {
        $this->text = $text;
        return $this;
    }

    /**
     * @return Addition[]
     */
    public function getAdditions(): array
    {
        return $this->additions;
    }

    /**
     * @param Addition[] $additions
     *
     * @return static
     */
    public function setAdditions(?array $additions = []): self
    {
        $this->additions = [];

        if (null !== $additions) {
            foreach ($additions as $addition) {
                $this->addAddition($addition);
            }
        }

        return $this;
    }

    /**
     * @param Addition $addition
     *
     * @return static
     */
    public function addAddition(?Addition $addition = null): self
    {
        if (null !== $addition) {
            $addition->validate();
            $this->additions[] = $addition;
        }

        return $this;
    }

    /**
     * @param Addition[] $additions
     *
     * @return static
     */
    public function addAdditions(?array $additions = []): self
    {
        if (null !== $additions) {
            foreach ($additions as $addition) {
                $this->addAddition($addition);
            }
        }

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
    public function setFormat(string $format = 'none'): self
    {
        Assertion::inArray($format, ['none', 'html', 'markdown'], 'format must be one of the following values: none, html, markdown.');
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

    /**
     * @param InlineTextStyle[] $inlineTextStyles
     *
     * @return static
     */
    public function setInlineTextStyles(?array $inlineTextStyles = []): self
    {
        $this->inlineTextStyles = [];

        if (null !== $inlineTextStyles) {
            foreach ($inlineTextStyles as $inlineTextStyle) {
                $this->addInlineTextStyle($inlineTextStyle);
            }
        }

        return $this;
    }

    /**
     * @param InlineTextStyle $inlineTextStyle
     *
     * @return static
     */
    public function addInlineTextStyle(?InlineTextStyle $inlineTextStyle = null): self
    {
        if (null !== $inlineTextStyle) {
            $inlineTextStyle->validate();
            $this->inlineTextStyles[] = $inlineTextStyle;
        }

        return $this;
    }

    /**
     * @param InlineTextStyle[] $inlineTextStyles
     *
     * @return static
     */
    public function addInlineTextStyles(?array $inlineTextStyles = []): self
    {
        if (null !== $inlineTextStyles) {
            foreach ($inlineTextStyles as $inlineTextStyle) {
                $this->addInlineTextStyle($inlineTextStyle);
            }
        }

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

    /**
     * {@inheritdoc}
     */
    public function validate(): void
    {
        Assertion::notNull($this->text);
        Assertion::notEmpty($this->text);
    }
}
