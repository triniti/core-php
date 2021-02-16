<?php
declare(strict_types=1);

namespace Triniti\AppleNews\Behavior;

/**
 * @link https://developer.apple.com/documentation/apple_news/background_parallax
 */
class BackgroundParallax extends Behavior
{
    public function jsonSerialize()
    {
        $properties = $this->getSetProperties();
        $properties['type'] = 'background_parallax';
        return $properties;
    }
}