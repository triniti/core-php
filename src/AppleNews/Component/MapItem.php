<?php
declare(strict_types=1);

namespace Triniti\AppleNews\Component;

use Assert\Assertion;
use Triniti\AppleNews\AppleNewsObject;

/**
 * @link https://developer.apple.com/documentation/apple_news/map_item
 */
class MapItem extends AppleNewsObject
{
    protected ?string $caption = null;
    protected ?float $latitude = null;
    protected ?float $longitude = null;

    public function getCaption(): ?string
    {
        return $this->caption;
    }

    public function setCaption(?string $caption = null): self
    {
        $this->caption = $caption;
        return $this;
    }

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

    public function jsonSerialize()
    {
        return $this->getSetProperties();
    }

    public function validate(): void
    {
        Assertion::notNull($this->latitude);
        Assertion::notNull($this->longitude);
    }
}
