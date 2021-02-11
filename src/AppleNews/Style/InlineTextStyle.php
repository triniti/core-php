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
    protected ?int $rangeLength = null;
    protected ?int $rangeStart = null;

    /** @var string|TextStyle */
    protected $textStyle;

    public function getRangeLength(): ?int
    {
        return $this->rangeLength;
    }

    public function setRangeLength(int $rangeLength): self
    {
        $this->rangeLength = $rangeLength;
        return $this;
    }

    public function getRangeStart(): ?int
    {
        return $this->rangeStart;
    }

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

    public function jsonSerialize()
    {
        return $this->getSetProperties();
    }

    public function validate(): void
    {
        Assertion::notNull($this->rangeStart);
        Assertion::notNull($this->rangeLength);
        Assertion::notNull($this->textStyle);
    }
}
