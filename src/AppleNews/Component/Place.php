<?php
declare(strict_types=1);

namespace Triniti\AppleNews\Component;

use Assert\Assertion;

/**
 * @link https://developer.apple.com/documentation/apple_news/place
 */
class Place extends Component
{
    /** @var float */
    protected $latitude;

    /** @var float */
    protected $longitude;

    /** @var string */
    protected $accessibilityCaption;

    /** @var string */
    protected $caption;

    /** @var string */
    protected $mapType;

    /** @var MapSpan */
    protected $span;

    /**
     * @return float
     */
    public function getLatitude(): ?float
    {
        return $this->latitude;
    }

    /**
     * @param float $latitude
     *
     * @return static
     */
    public function setLatitude(float $latitude): self
    {
        $this->latitude = $latitude;
        return $this;
    }

    /**
     * @return float
     */
    public function getLongitude(): ?float
    {
        return $this->longitude;
    }

    /**
     * @param float $longitude
     *
     * @return static
     */
    public function setLongitude(float $longitude): self
    {
        $this->longitude = $longitude;
        return $this;
    }

    /**
     * @return string
     */
    public function getAccessibilityCaption(): ?string
    {
        return $this->accessibilityCaption;
    }

    /**
     * @param string $accessibilityCaption
     *
     * @return static
     */
    public function setAccessibilityCaption(?string $accessibilityCaption = null): self
    {
        $this->accessibilityCaption = $accessibilityCaption;
        return $this;
    }

    /**
     * @return string
     */
    public function getCaption(): ?string
    {
        return $this->caption;
    }

    /**
     * @param string $caption
     *
     * @return static
     */
    public function setCaption(?string $caption = null): self
    {
        $this->caption = $caption;
        return $this;
    }

    /**
     * @return string
     */
    public function getMapType(): ?string
    {
        return $this->mapType;
    }

    /**
     * @param string $mapType
     *
     * @return static
     */
    public function setMapType(?string $mapType = 'standard'): self
    {
        if (null === $mapType) {
            $mapType = 'standard';
        }

        Assertion::inArray($mapType, ['standard', 'hybrid', 'satellite'], 'MapType must be one of the following values: standard, hybrid, satellite.');
        $this->mapType = $mapType;
        return $this;
    }

    /**
     * @return MapSpan
     */
    public function getSpan(): ?MapSpan
    {
        return $this->span;
    }

    /**
     * @param MapSpan $span
     *
     * @return static
     */
    public function setSpan(?MapSpan $span = null): self
    {
        if (null !== $span) {
            $span->validate();
        }

        $this->span = $span;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function validate(): void
    {
        Assertion::notNull($this->latitude);
        Assertion::notNull($this->longitude);
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        $properties = $this->getSetProperties();
        $properties['role'] = 'place';
        return $properties;
    }
}
