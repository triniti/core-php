<?php
declare(strict_types=1);

namespace Triniti\AppleNews;

/**
 * @link https://developer.apple.com/documentation/apple_news/autoplacement
 */
class AutoPlacement extends AppleNewsObject
{
    /** @var AdvertisementAutoPlacement */
    protected $advertisement;

    /**
     * @return AdvertisementAutoPlacement
     */
    public function getAdvertisement(): ?AdvertisementAutoPlacement
    {
        return $this->advertisement;
    }

    /**
     * @param AdvertisementAutoPlacement $advertisement
     *
     * @return static
     */
    public function setAdvertisement(AdvertisementAutoPlacement $advertisement): self
    {
        $this->advertisement = $advertisement;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize(): mixed
    {
        return $this->getSetProperties();
    }
}
