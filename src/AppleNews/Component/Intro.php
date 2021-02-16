<?php
declare(strict_types=1);

namespace Triniti\AppleNews\Component;

/**
 * @link https://developer.apple.com/documentation/apple_news/intro
 */
class Intro extends Text
{
    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        $properties = $this->getSetProperties();
        $properties['role'] = 'intro';
        return $properties;
    }
}

