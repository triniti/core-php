<?php
declare(strict_types=1);

namespace Triniti\AppleNews\Component;

use Assert\Assertion;

/**
 * @link https://developer.apple.com/documentation/apple_news/place
 */
class Place extends Component
{
    protected ?float $latitude = null;
    protected ?float $longitude = null;
    protected ?string $accessibilityCaption = null;
    protected ?string $caption = null;
    protected string $mapType;
    protected ?MapSpan $span = null;

    public function getLatitude(): ?float
    {
        return $this->latitude;
    }

    public function setLatitude(float $latitude): self
    {
        $this->latitude = $latitude;
        return $this;
    }

    public function getLongitude(): ?float
    {
        return $this->longitude;
    }

    public function setLongitude(float $longitude): self
    {
        $this->longitude = $longitude;
        return $this;
    }

    public function getAccessibilityCaption(): ?string
    {
        return $this->accessibilityCaption;
    }

    public function setAccessibilityCaption(?string $accessibilityCaption = null): self
    {
        $this->accessibilityCaption = $accessibilityCaption;
        return $this;
    }

    public function getCaption(): ?string
    {
        return $this->caption;
    }

    public function setCaption(?string $caption = null): self
    {
        $this->caption = $caption;
        return $this;
    }

    public function getMapType(): ?string
    {
        return $this->mapType;
    }

    public function setMapType(?string $mapType = 'standard'): self
    {
        if (null === $mapType) {
            $mapType = 'standard';
        }

        Assertion::inArray($mapType, ['standard', 'hybrid', 'satellite'], 'MapType must be one of the following values: standard, hybrid, satellite.');
        $this->mapType = $mapType;
        return $this;
    }

    public function getSpan(): ?MapSpan
    {
        return $this->span;
    }

    public function setSpan(?MapSpan $span = null): self
    {
        if (null !== $span) {
            $span->validate();
        }

        $this->span = $span;
        return $this;
    }

    public function validate(): void
    {
        Assertion::notNull($this->latitude);
        Assertion::notNull($this->longitude);
    }

    public function jsonSerialize()
    {
        $properties = $this->getSetProperties();
        $properties['role'] = 'place';
        return $properties;
    }
}
