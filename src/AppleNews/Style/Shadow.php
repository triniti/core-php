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
    /** @var string */
    protected $color;

    /** @var Offset */
    protected $offset;

    /** @var float */
    protected $opacity;

    /** @var integer */
    protected $radius;

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
     * @return Offset
     */
    public function getOffset(): ?Offset
    {
        return $this->offset;
    }

    /**
     * @param Offset $offset
     *
     * @return static
     */
    public function setOffset(?Offset $offset = null): self
    {
        if (null !== $offset) {
            $offset->validate();
        }

        $this->offset = $offset;
        return $this;
    }

    /**
     * @return float
     */
    public function getOpacity(): ?float
    {
        return $this->opacity;
    }

    /**
     * @param float $opacity
     *
     * @return static
     */
    public function setOpacity(float $opacity = 1): self
    {
        Assertion::between($opacity, 0, 1.0);
        $this->opacity = $opacity;
        return $this;
    }

    /**
     * @return int
     */
    public function getRadius(): ?int
    {
        return $this->radius;
    }

    /**
     * @param int $radius
     *
     * @return static
     */
    public function setRadius(int $radius = 0): self
    {
        Assertion::between($radius, 0, 100);
        $this->radius = $radius;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function validate(): void
    {
        Assertion::notNull($this->color);
        Assertion::notNull($this->radius);
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize(): array
    {
        return $this->getSetProperties();
    }
}
