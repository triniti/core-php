<?php
declare(strict_types=1);

namespace Triniti\AppleNews\Behavior;

/**
 * @link https://developer.apple.com/documentation/apple_news/motion
 */
class Motion extends Behavior
{
    /**
     * {@inheritdoc}
     */
    public function jsonSerialize(): mixed
    {
        $properties = $this->getSetProperties();
        $properties['type'] = 'motion';
        return $properties;
    }
}

