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
    protected ?string $color = null;
    protected string $style = 'solid';

    /** @var SupportedUnits|int */
    protected $width = 1;

    /**
     * @var string[] Valid styles
     */
    private array $validStyles = [
        'solid',
        'dashed',
        'dotted',
    ];

    public function getColor(): ?string
    {
        return $this->color;
    }

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

    public function setWidth($width = 1): self
    {
        $this->width = $width;
        return $this;
    }

    public function getStyle()
    {
        return $this->style;
    }

    public function setStyle(string $style = 'solid'): self
    {
        Assertion::inArray($style, $this->validStyles);
        $this->style = $style;
        return $this;
    }

    public function jsonSerialize()
    {
        return $this->getSetProperties();
    }
}

