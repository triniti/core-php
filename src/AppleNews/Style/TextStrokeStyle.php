<?php
declare(strict_types=1);

namespace Triniti\AppleNews\Style;

use Assert\Assertion;
use Triniti\AppleNews\AppleNewsObject;

/**
 * @link https://developer.apple.com/documentation/apple_news/text_stroke_style
 */
class TextStrokeStyle extends AppleNewsObject
{
    protected string $color;
    protected int $width = 3;

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(?string $color = null): self
    {
        $this->color = $color;
        return $this;
    }

    public function getWidth(): int
    {
        return $this->width;
    }

    public function setWidth(int $width = 3): self
    {
        Assertion::greaterOrEqualThan($width, 0);
        $this->width = $width;
        return $this;
    }

    public function jsonSerialize()
    {
        return $this->getSetProperties();
    }
}
