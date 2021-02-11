<?php
declare(strict_types=1);

namespace Triniti\AppleNews\Component;

/**
 * @link https://developer.apple.com/documentation/apple_news/author
 */
class Author extends Text
{
    public function jsonSerialize()
    {
        $properties = $this->getSetProperties();
        $properties['role'] = 'author';
        return $properties;
    }
}
