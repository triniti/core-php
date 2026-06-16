<?php
declare(strict_types=1);

namespace Triniti\AppleNews\Component;

use Triniti\AppleNews\Scene\Scene;

/**
 * @link https://developer.apple.com/documentation/applenewsformat/section
 */
class Section extends Container
{
    /** @var Scene */
    protected $scene;

    /** @var bool */
    protected $allowAutoplacedAds = true;

    /**
     * @return Scene
     */
    public function getScene(): ?Scene
    {
        return $this->scene;
    }

    /**
     * @param Scene $scene
     *
     * @return static
     */
    public function setScene(?Scene $scene = null): self
    {
        if (null !== $scene) {
            $scene->validate();
        }

        $this->scene = $scene;
        return $this;
    }

    /**
     * @return bool
     */
    public function getAllowAutoplacedAds(): bool
    {
        return $this->allowAutoplacedAds;
    }

    /**
     * @param bool $allowAutoplacedAds
     *
     * @return static
     */
    public function setAllowAutoplacedAds(bool $allowAutoplacedAds = true): self
    {
        $this->allowAutoplacedAds = $allowAutoplacedAds;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize(): array
    {
        $properties = $this->getSetProperties();
        $properties['role'] = 'section';
        return $properties;
    }
}
