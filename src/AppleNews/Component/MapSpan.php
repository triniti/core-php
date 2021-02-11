<?php
declare(strict_types=1);

namespace Triniti\AppleNews\Component;

use Assert\Assertion;
use Triniti\AppleNews\AppleNewsObject;

/**
 * @link https://developer.apple.com/documentation/apple_news/map_span
 */
class MapSpan extends AppleNewsObject
{
    protected ?float $latitudeDelta = null;
    protected ?float $longitudeDelta = null;

    public function getLatitudeDelta(): ?float
    {
        return $this->latitudeDelta;
    }

    public function setLatitudeDelta(float $latitudeDelta): self
    {
        Assertion::between($latitudeDelta, 0.0, 90.0);
        $this->latitudeDelta = $latitudeDelta;
        return $this;
    }

    public function getLongitudeDelta(): ?float
    {
        return $this->longitudeDelta;
    }

    public function setLongitudeDelta(float $longitudeDelta): self
    {
        Assertion::between($longitudeDelta, 0.0, 180.0);
        $this->longitudeDelta = $longitudeDelta;
        return $this;
    }

    public function jsonSerialize()
    {
        return $this->getSetProperties();
    }

    public function validate(): void
    {
        Assertion::notNull($this->latitudeDelta);
        Assertion::notNull($this->longitudeDelta);
    }
}
