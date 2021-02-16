<?php

declare(strict_types=1);

namespace Triniti\AppleNews;

/**
 * @link https://developer.apple.com/documentation/apple_news/horizontalstackdisplay
 */
class HorizontalStackDisplay extends AppleNewsObject
{
    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        $properties = $this->getSetProperties();
        $properties['type'] = 'horizontal_stack';
        return $properties;
    }
}

