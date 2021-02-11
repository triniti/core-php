<?php
declare(strict_types=1);

namespace Triniti\AppleNews;

/**
 * @link https://developer.apple.com/documentation/apple_news/autoplacement
 */
class AutoPlacement extends AppleNewsObject
{
    protected ?AdvertisementAutoPlacement $advertisement = null;

    public function getAdvertisement(): ?AdvertisementAutoPlacement
    {
        return $this->advertisement;
    }

    public function setAdvertisement(AdvertisementAutoPlacement $advertisement): self
    {
        $this->advertisement = $advertisement;
        return $this;
    }

    public function jsonSerialize()
    {
        return $this->getSetProperties();
    }
}
