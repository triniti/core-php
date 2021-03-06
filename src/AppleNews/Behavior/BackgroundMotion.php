<?php
declare(strict_types=1);

namespace Triniti\AppleNews\Behavior;

/**
 * @link https://developer.apple.com/documentation/apple_news/background_motion
 */
class BackgroundMotion extends Behavior
{
    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        $properties = $this->getSetProperties();
        $properties['type'] = 'background_motion';
        return $properties;
    }
}
