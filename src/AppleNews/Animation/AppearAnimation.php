<?php
declare(strict_types=1);

namespace Triniti\AppleNews\Animation;

class AppearAnimation extends ComponentAnimation
{
    /**
     * {@inheritdoc}
     */
    public function jsonSerialize(): mixed
    {
        $properties = $this->getSetProperties();
        $properties['type'] = 'appear';
        return $properties;
    }
}
