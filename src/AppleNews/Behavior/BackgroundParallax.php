<?php
declare(strict_types=1);

namespace Triniti\AppleNews\Behavior;

/**
 * @link https://developer.apple.com/documentation/apple_news/background_parallax
 */
class BackgroundParallax extends Behavior
{
    /**
     * {@inheritdoc}
     */
    public function jsonSerialize(): array
    {
        $properties = $this->getSetProperties();
        $properties['type'] = 'background_parallax';
        return $properties;
    }
}
