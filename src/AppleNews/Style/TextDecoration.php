<?php
declare(strict_types=1);

namespace Triniti\AppleNews\Style;

use Triniti\AppleNews\AppleNewsObject;

/**
 * @link https://developer.apple.com/documentation/apple_news/text_decoration
 */
class TextDecoration extends AppleNewsObject
{
    protected ?string $color = null;

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(?string $color = null): self
    {
        $this->color = $color;
        return $this;
    }

    public function jsonSerialize()
    {
        return $this->getSetProperties();
    }
}
