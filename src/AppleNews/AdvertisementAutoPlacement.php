<?php
declare(strict_types=1);

namespace Triniti\AppleNews;

use Assert\Assertion;
use Triniti\AppleNews\Layout\AutoPlacementLayout;

/**
 * @link https://developer.apple.com/documentation/apple_news/advertisementautoplacement
 */
class AdvertisementAutoPlacement extends AppleNewsObject
{
    /** @var string */
    protected $bannerType;

    /** @var ConditionalAutoPlacement[] */
    protected $conditional;

    /** @var int|SupportedUnits */
    protected $distanceFromMedia;

    /** @var bool */
    protected $enabled = false;

    /** @var int */
    protected $frequency;

    /** @var AutoPlacementLayout */
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
     * @return ConditionalAutoPlacement[]
     */
    public function getConditional(): array
    {
        return $this->conditional;
    }

    /**
     * @param ConditionalAutoPlacement[] $conditionals
     *
     * @return static
     */
    public function setConditional(?array $conditionals = []): self
    {
        $this->conditional = [];

        if (null !== $conditionals) {
            foreach ($conditionals as $conditional) {
                $this->addConditional($conditional);
            }
        }

        return $this;
    }

    public function addConditional(?ConditionalAutoPlacement $conditional = null): self
    {
        if (null !== $conditional) {
            $conditional->validate();
            $this->conditional[] = $conditional;
        }

        return $this;
    }

    /**
     * @param ConditionalAutoPlacement[] $conditionals
     *
     * @return static
     */
    public function addConditionals(?array $conditionals = []): self
    {
        if (null !== $conditionals) {
            foreach ($conditionals as $conditional) {
                $this->addConditional($conditional);
            }
        }

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

    public function getEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled = false): self
    {
        $this->enabled = $enabled;
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

        Assertion::between($frequency, 0, 10, 'Frequency must be number between 0 and 10');
        $this->frequency = $frequency;
        return $this;
    }

    public function getLayout(): ?AutoPlacementLayout
    {
        return $this->layout;
    }

    public function setLayout(?AutoPlacementLayout $layout = null): self
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
