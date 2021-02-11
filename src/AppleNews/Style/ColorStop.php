<?php
declare(strict_types=1);

namespace Triniti\AppleNews\Style;

use Assert\Assertion;
use Triniti\AppleNews\AppleNewsObject;

class ColorStop extends AppleNewsObject
{
    protected ?string $color = null;
    protected ?int $location = null;

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(string $color): self
    {
        $this->color = $color;
        return $this;
    }

    public function getLocation(): ?int
    {
        return $this->location;
    }

    public function setLocation(int $location): self
    {
        Assertion::greaterOrEqualThan($location, 0);
        Assertion::lessOrEqualThan($location, 100);
        $this->location = $location;
        return $this;
    }

    public function validate(): void
    {
        Assertion::notNull($this->color);
    }

    public function jsonSerialize()
    {
        return $this->getSetProperties();
    }
}
