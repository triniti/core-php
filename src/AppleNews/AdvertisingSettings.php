<?php
declare(strict_types=1);

namespace Triniti\AppleNews;

use Assert\Assertion;
use Triniti\AppleNews\Layout\AdvertisingLayout;

class AdvertisingSettings extends AppleNewsObject
{
    /** @var string */
    protected $bannerType;

    /** @var int|SupportedUnits */
    protected $distanceFromMedia;

    /** @var int */
    protected $frequency;

    /** @var AdvertisingLayout */
    protected $layout;

    /**
     * @var string[]
     */
    private $validBannerTypes = [
        'any',
        'standard',
        'double_height',
        'large',
    ];

    /**
     * @return string
     */
    public function getBannerType(): ?string
    {
        return $this->bannerType;
    }

    /**
     * @param string $bannerType
     *
     * @return static
     */
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

    /**
     * @return int
     */
    public function getFrequency(): ?int
    {
        return $this->frequency;
    }

    /**
     * @param int $frequency
     *
     * @return AdvertisingSettings
     */
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

    /**
     * @return AdvertisingLayout
     */
    public function getLayout(): ?AdvertisingLayout
    {
        return $this->layout;
    }

    /**
     * @param AdvertisingLayout $layout
     *
     * @return static
     */
    public function setLayout(?AdvertisingLayout $layout = null): self
    {
        if ($layout) {
            $layout->validate();
        }

        $this->layout = $layout;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        return $this->getSetProperties();
    }
}
