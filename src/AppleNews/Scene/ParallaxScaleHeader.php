<?php
declare(strict_types=1);

namespace Triniti\AppleNews\Scene;

class ParallaxScaleHeader extends Scene
{
    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        $properties = $this->getSetProperties();
        $properties['type'] = 'parallax_scale';
        return $properties;
    }
}
