<?php
declare(strict_types=1);

namespace Triniti\AppleNews\Style;

use Assert\Assertion;
use Triniti\AppleNews\AppleNewsObject;

/**
 * @link https://developer.apple.com/documentation/apple_news/inline_text_style
 */
class InlineTextStyle extends AppleNewsObject
{
    /** @var int */
    protected $rangeLength;

    /** @var int */
    protected $rangeStart;

    /** @var string|TextStyle */
    protected $textStyle;

    /**
     * @return int
     */
    public function getRangeLength(): ?int
    {
        return $this->rangeLength;
    }

    /**
     * @param int $rangeLength
     *
     * @return static
     */
    public function setRangeLength(int $rangeLength): self
    {
        $this->rangeLength = $rangeLength;
        return $this;
    }

    /**
     * @return int
     */
    public function getRangeStart(): ?int
    {
        return $this->rangeStart;
    }

    /**
     * @param int $rangeStart
     *
     * @return static
     */
    public function setRangeStart(int $rangeStart): self
    {
        $this->rangeStart = $rangeStart;
        return $this;
    }

    /**
     * @return string|TextStyle
     */
    public function getTextStyle()
    {
        return $this->textStyle;
    }

    /**
     * @param string|TextStyle $textStyle
     *
     * @return static
     */
    public function setTextStyle($textStyle): self
    {
        $this->textStyle = $textStyle;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize(): array
    {
        return $this->getSetProperties();
    }

    /**
     * {@inheritdoc}
     */
    public function validate(): void
    {
        Assertion::notNull($this->rangeStart);
        Assertion::notNull($this->rangeLength);
        Assertion::notNull($this->textStyle);
    }
}
