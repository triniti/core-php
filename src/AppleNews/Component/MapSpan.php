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
    /** @var float */
    protected $latitudeDelta;

    /** @var float */
    protected $longitudeDelta;

    /**
     * @return float
     */
    public function getLatitudeDelta(): ?float
    {
        return $this->latitudeDelta;
    }

    /**
     * @param float $latitudeDelta
     *
     * @return static
     */
    public function setLatitudeDelta(float $latitudeDelta): self
    {
        Assertion::between($latitudeDelta, 0.0, 90.0);
        $this->latitudeDelta = $latitudeDelta;
        return $this;
    }

    /**
     * @return float
     */
    public function getLongitudeDelta(): ?float
    {
        return $this->longitudeDelta;
    }

    /**
     * @param float $longitudeDelta
     *
     * @return static
     */
    public function setLongitudeDelta(float $longitudeDelta): self
    {
        Assertion::between($longitudeDelta, 0.0, 180.0);
        $this->longitudeDelta = $longitudeDelta;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize(): array
    {
        return $this->getSetProperties();
    }

    /**
     * {@inheritdoc}
     */
    public function validate(): void
    {
        Assertion::notNull($this->latitudeDelta);
        Assertion::notNull($this->longitudeDelta);
    }
}
