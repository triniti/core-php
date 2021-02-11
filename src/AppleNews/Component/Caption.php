<?php
declare(strict_types=1);

namespace Triniti\AppleNews\Component;

/**
 * @link https://developer.apple.com/documentation/apple_news/caption
 */
class Caption extends Text
{
    public function jsonSerialize()
    {
        $properties = $this->getSetProperties();
        $properties['role'] = 'caption';
        return $properties;
    }
}
