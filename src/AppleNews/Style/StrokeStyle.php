<?php
declare(strict_types=1);

namespace Triniti\AppleNews\Style;

use Assert\Assertion;
use Triniti\AppleNews\AppleNewsObject;
use Triniti\AppleNews\SupportedUnits;

/**
 * @link https://developer.apple.com/documentation/apple_news/stroke_style
 */
class StrokeStyle extends AppleNewsObject
{
    /** @var string */
    protected $color;

    /** @var SupportedUnits|int */
    protected $width = 1;

    /** @var string */
    protected $style = 'solid';

    /**
     * @var string[] Valid styles
     */
    private $validStyles = [
        'solid',
        'dashed',
        'dotted',
    ];

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
     * @return int|string
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * @param int $width
     *
     * @return static
     */
    public function setWidth($width = 1): self
    {
        $this->width = $width;
        return $this;
    }

    /**
     * @return string
     */
    public function getStyle()
    {
        return $this->style;
    }

    /**
     * @param string $style
     *
     * @return static
     */
    public function setStyle(string $style = 'solid'): self
    {
        Assertion::inArray($style, $this->validStyles);
        $this->style = $style;
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

