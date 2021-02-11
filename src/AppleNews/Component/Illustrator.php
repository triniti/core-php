<?php
declare(strict_types=1);

namespace Triniti\AppleNews\Component;

/**
 * @link https://developer.apple.com/documentation/apple_news/illustrator
 */
class Illustrator extends Text
{
    public function jsonSerialize()
    {
        $properties = $this->getSetProperties();
        $properties['role'] = 'illustrator';
        return $properties;
    }
}
