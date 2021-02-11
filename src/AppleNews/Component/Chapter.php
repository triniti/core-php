<?php
declare(strict_types=1);

namespace Triniti\AppleNews\Component;

use Triniti\AppleNews\Scene\Scene;

/**
 * @link https://developer.apple.com/documentation/apple_news/chapter
 */
class Chapter extends Container
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
        $properties['role'] = 'chapter';
        return $properties;
    }
}
