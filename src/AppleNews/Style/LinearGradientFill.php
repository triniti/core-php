<?php
declare(strict_types=1);

namespace Triniti\AppleNews\Style;

class LinearGradientFill extends GradientFill
{
    /** @var int */
    protected $angle;

    /**
     * @return int
     */
    public function getAngle(): ?int
    {
        return $this->angle;
    }

    /**
     * @param int $angle
     *
     * @return static
     */
    public function setAngle(?int $angle = 180): self
    {
        $this->angle = $angle;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize(): array
    {
        $properties = $this->getSetProperties();
        $properties['type'] = 'linear_gradient';
        return $properties;
    }
}
