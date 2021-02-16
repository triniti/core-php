<?php
declare(strict_types=1);

namespace Triniti\AppleNews\Style;

use Assert\Assertion;

abstract class GradientFill extends Fill
{
    /** @var ColorStop[] */
    protected $colorStops = [];

    /**
     * @return ColorStop[]
     */
    public function getColorStops(): array
    {
        return $this->colorStops;
    }

    /**
     * @param ColorStop $colorStop
     *
     * @return static
     */
    public function addColorStop(?ColorStop $colorStop = null): self
    {
        if (null === $colorStop) {
            return $this;
        }

        $colorStop->validate();
        $this->colorStops[] = $colorStop;
        return $this;
    }

    /**
     * @param ColorStop[] $colorStops
     *
     * @return static
     */
    public function addColorStops(?array $colorStops = []): self
    {
        foreach ($colorStops as $colorStop) {
            $this->addColorStop($colorStop);
        }

        return $this;
    }

    /**
     * @param array $colorStops
     *
     * @return static
     */
    public function setColorStops(?array $colorStops = []): self
    {
        $this->colorStops = [];
        $this->addColorStops($colorStops);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function validate(): void
    {
        Assertion::notNull($this->colorStops);
        Assertion::isArray($this->colorStops);
    }
}
