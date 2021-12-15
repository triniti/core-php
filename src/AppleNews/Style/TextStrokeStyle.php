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
    /** @var string */
    protected $color;

    /** @var int */
    protected $width = 3;

    /**
     * @return string
     */
    public function getColor(): ?string
    {
        return $this->color;
    }

    /**
     * @param string $color
     *
     * @return static
     */
    public function setColor(?string $color = null): self
    {
        $this->color = $color;
        return $this;
    }

    /**
     * @return int
     */
    public function getWidth(): int
    {
        return $this->width;
    }

    /**
     * @param int $width
     *
     * @return static
     */
    public function setWidth(int $width = 3): self
    {
        Assertion::greaterOrEqualThan($width, 0);
        $this->width = $width;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize(): mixed
    {
        return $this->getSetProperties();
    }
}
