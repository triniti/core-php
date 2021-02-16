<?php
declare(strict_types=1);

namespace Triniti\AppleNews\Behavior;

/**
 * @link https://developer.apple.com/documentation/apple_news/springy
 */
class Springy extends Behavior
{
    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        $properties = $this->getSetProperties();
        $properties['type'] = 'springy';
        return $properties;
    }
}

