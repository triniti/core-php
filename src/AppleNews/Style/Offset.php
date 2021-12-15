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
    /** @var float */
    protected $x;

    /** @var float */
    protected $y;

    /**
     * @return float
     */
    public function getX(): ?float
    {
        return $this->x;
    }

    /**
     * @param float $x
     *
     * @return static
     */
    public function setX(float $x): self
    {
        Assertion::between($x, -50, 50);
        $this->x = $x;
        return $this;
    }

    /**
     * @return float
     */
    public function getY(): ?float
    {
        return $this->y;
    }

    /**
     * @param float $y
     *
     * @return static
     */
    public function setY(float $y): self
    {
        Assertion::between($y, -50, 50);
        $this->y = $y;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize(): mixed
    {
        return $this->getSetProperties();
    }

    /**
     * {@inheritdoc}
     */
    public function validate(): void
    {
        Assertion::notNull($this->x);
        Assertion::notNull($this->y);
    }
}
