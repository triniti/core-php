<?php
declare(strict_types=1);

namespace Triniti\AppleNews\Scene;

/**
 * @link https://developer.apple.com/documentation/apple_news/fading_sticky_header
 */
class FadingStickyHeader extends Scene
{
    /** @var string */
    protected $fadeColor = '#000000';

    /**
     * @return string
     */
    public function getFadeColor(): string
    {
        return $this->fadeColor;
    }

    /**
     * @param string $fadeColor
     *
     * @return static
     */
    public function setFadeColor(string $fadeColor = '#000000'): self
    {
        $this->fadeColor = $fadeColor;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize(): mixed
    {
        $properties = $this->getSetProperties();
        $properties['type'] = 'fading_sticky_header';
        return $properties;
    }
}
