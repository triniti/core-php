<?php
declare(strict_types=1);

namespace Triniti\AppleNews\Style;

use Assert\Assertion;
use Triniti\AppleNews\AppleNewsObject;

/**
 * @link https://developer.apple.com/documentation/apple_news/shadow
 */
class Shadow extends AppleNewsObject
{
    protected ?string $color = null;
    protected ?Offset $offset = null;
    protected ?float $opacity = null;
    protected ?int $radius = null;

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(string $color): self
    {
        $this->color = $color;
        return $this;
    }

    public function getOffset(): ?Offset
    {
        return $this->offset;
    }

    public function setOffset(?Offset $offset = null): self
    {
        if (null !== $offset) {
            $offset->validate();
        }

        $this->offset = $offset;
        return $this;
    }

    public function getOpacity(): ?float
    {
        return $this->opacity;
    }

    public function setOpacity(float $opacity = 1): self
    {
        Assertion::between($opacity, 0, 1.0);
        $this->opacity = $opacity;
        return $this;
    }

    public function getRadius(): ?int
    {
        return $this->radius;
    }

    public function setRadius(int $radius = 0): self
    {
        Assertion::between($radius, 0, 100);
        $this->radius = $radius;
        return $this;
    }

    public function validate(): void
    {
        Assertion::notNull($this->color);
        Assertion::notNull($this->radius);
    }

    public function jsonSerialize()
    {
        return $this->getSetProperties();
    }
}
