<?php
declare(strict_types=1);

namespace Triniti\AppleNews\Style;

use Assert\Assertion;
use Triniti\AppleNews\AppleNewsObject;

class ColorStop extends AppleNewsObject
{
    /** @var string */
    protected $color;

    /** @var int */
    protected $location;

    /**
     * @return string
     */
    public function getColor(): ?string
    {
        return $this->color;
    }

    /**
     * @param string $color
     *
     * @return static
     */
    public function setColor(string $color): self
    {
        $this->color = $color;
        return $this;
    }

    /**
     * @return int
     */
    public function getLocation(): ?int
    {
        return $this->location;
    }

    /**
     * @param int $location
     *
     * @return static
     */
    public function setLocation(int $location): self
    {
        Assertion::greaterOrEqualThan($location, 0);
        Assertion::lessOrEqualThan($location, 100);
        $this->location = $location;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function validate(): void
    {
        Assertion::notNull($this->color);
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        return $this->getSetProperties();
    }
}
