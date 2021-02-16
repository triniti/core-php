<?php
declare(strict_types=1);

namespace Triniti\AppleNews\Component;

/**
 * @link https://developer.apple.com/documentation/apple_news/title
 */
class Title extends Text
{
    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        $properties = $this->getSetProperties();
        $properties['role'] = 'title';
        return $properties;
    }
}
