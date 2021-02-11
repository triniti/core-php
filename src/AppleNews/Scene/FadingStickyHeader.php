<?php
declare(strict_types=1);

namespace Triniti\AppleNews\Scene;

/**
 * @link https://developer.apple.com/documentation/apple_news/fading_sticky_header
 */
class FadingStickyHeader extends Scene
{
    protected string $fadeColor = '#000000';

    public function getFadeColor(): string
    {
        return $this->fadeColor;
    }

    public function setFadeColor(string $fadeColor = '#000000'): self
    {
        $this->fadeColor = $fadeColor;
        return $this;
    }

    public function jsonSerialize()
    {
        $properties = $this->getSetProperties();
        $properties['type'] = 'fading_sticky_header';
        return $properties;
    }
}
