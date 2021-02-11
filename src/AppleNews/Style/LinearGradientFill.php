<?php
declare(strict_types=1);

namespace Triniti\AppleNews\Style;

class LinearGradientFill extends GradientFill
{
    protected ?int $angle = null;

    public function getAngle(): ?int
    {
        return $this->angle;
    }

    public function setAngle(?int $angle = 180): self
    {
        $this->angle = $angle;
        return $this;
    }

    public function jsonSerialize()
    {
        $properties = $this->getSetProperties();
        $properties['type'] = 'linear_gradient';
        return $properties;
    }
}
