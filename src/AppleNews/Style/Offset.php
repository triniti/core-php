<?php
declare(strict_types=1);

namespace Triniti\AppleNews\Style;

use Assert\Assertion;
use Triniti\AppleNews\AppleNewsObject;

/**
 * @link https://developer.apple.com/documentation/apple_news/offset
 */
class Offset extends AppleNewsObject
{
    protected ?float $x = null;
    protected ?float $y = null;

    public function getX(): ?float
    {
        return $this->x;
    }

    public function setX(float $x): self
    {
        Assertion::between($x, -50, 50);
        $this->x = $x;
        return $this;
    }

    public function getY(): ?float
    {
        return $this->y;
    }

    public function setY(float $y): self
    {
        Assertion::between($y, -50, 50);
        $this->y = $y;
        return $this;
    }

    public function jsonSerialize()
    {
        return $this->getSetProperties();
    }

    public function validate(): void
    {
        Assertion::notNull($this->x);
        Assertion::notNull($this->y);
    }
}