<?php
declare(strict_types=1);

namespace Triniti\AppleNews\Component;

use Assert\Assertion;

/**
 * @link https://developer.apple.com/documentation/apple_news/banner_advertisement
 */
class BannerAdvertisement extends Component
{
    protected string $bannerType;

    public function getBannerType(): ?string
    {
        return $this->bannerType;
    }

    public function setBannerType(string $bannerType = 'any'): self
    {
        Assertion::inArray(
            $bannerType,
            ['any', 'standard', 'double_height', 'large'],
            'Banner Type must be one of the following values: any, standard, double_height, large.'
        );

        $this->bannerType = $bannerType;
        return $this;
    }

    public function jsonSerialize()
    {
        $properties = $this->getSetProperties();
        $properties['role'] = 'banner_advertisement';
        return $properties;
    }
}
