<?php
declare(strict_types=1);

namespace Triniti\AppleNews\Component;

/**
 * @link https://developer.apple.com/documentation/apple_news/aside
 */
class Aside extends Container
{
    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        $properties = $this->getSetProperties();
        $properties['role'] = 'aside';
        return $properties;
    }
}
