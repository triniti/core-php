<?php
declare(strict_types=1);

namespace Triniti\AppleNews;

use Assert\Assertion;
use Triniti\AppleNews\Layout\AdvertisingLayout;

class AdvertisingSettings extends AppleNewsObject
{
    protected ?string $bannerType = null;
    protected ?int $frequency = null;
    protected ?AdvertisingLayout $layout = null;

    /** @var int|SupportedUnits */
    protected $distanceFromMedia;

    /**
     * @var string[]
     */
    private array $validBannerTypes = [
        'any',
        'standard',
        'double_height',
        'large',
    ];

    public function getBannerType(): ?string
    {
        return $this->bannerType;
    }

    public function setBannerType(?string $bannerType = 'any'): self
    {
        if (null === $bannerType) {
            $this->bannerType = 'any';
            return $this;
        }

        Assertion::inArray($bannerType, $this->validBannerTypes);
        $this->bannerType = $bannerType;
        return $this;
    }

    /**
     * @return int|SupportedUnits
     */
    public function getDistanceFromMedia()
    {
        return $this->distanceFromMedia;
    }

    /**
     * @param int|SupportedUnits $distanceFromMedia
     *
     * @return static
     */
    public function setDistanceFromMedia($distanceFromMedia = null): self
    {
        if (!is_int($distanceFromMedia) && null !== $distanceFromMedia && !$distanceFromMedia instanceof SupportedUnits) {
            Assertion::true(false, 'distanceFromMedia must be an int or instance of SupportedUnits.');
        }

        if ($distanceFromMedia instanceof SupportedUnits) {
            $distanceFromMedia->validate();
        }

        $this->distanceFromMedia = $distanceFromMedia;
        return $this;
    }

    public function getFrequency(): ?int
    {
        return $this->frequency;
    }

    public function setFrequency(?int $frequency = 0): self
    {
        if (null === $frequency) {
            $this->frequency = 0;
            return $this;
        }

        Assertion::between($frequency, 0, 10, 'Frequency is number between 0 and 10');
        $this->frequency = $frequency;
        return $this;
    }

    public function getLayout(): ?AdvertisingLayout
    {
        return $this->layout;
    }

    public function setLayout(?AdvertisingLayout $layout = null): self
    {
        if ($layout) {
            $layout->validate();
        }

        $this->layout = $layout;
        return $this;
    }

    public function jsonSerialize()
    {
        return $this->getSetProperties();
    }
}
