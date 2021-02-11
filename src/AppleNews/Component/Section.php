<?php
declare(strict_types=1);

namespace Triniti\AppleNews\Component;

use Triniti\AppleNews\Scene\Scene;

/**
 * @link https://developer.apple.com/documentation/apple_news/section-ka8
 */
class Section extends Container
{
    protected ?Scene $scene = null;

    public function getScene(): ?Scene
    {
        return $this->scene;
    }

    public function setScene(?Scene $scene = null): self
    {
        if (null !== $scene) {
            $scene->validate();
        }

        $this->scene = $scene;
        return $this;
    }

    public function jsonSerialize()
    {
        $properties = $this->getSetProperties();
        $properties['role'] = 'section';
        return $properties;
    }
}
