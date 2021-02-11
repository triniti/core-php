<?php
declare(strict_types=1);

namespace Triniti\AppleNews\Component;

use Triniti\AppleNews\Style\StrokeStyle;

/**
 * @link https://developer.apple.com/documentation/apple_news/divider
 */
class Divider extends Component
{
    protected ?StrokeStyle $stroke = null;

    public function getStroke(): ?StrokeStyle
    {
        return $this->stroke;
    }

    public function setStroke(?StrokeStyle $stroke = null): self
    {
        if (null !== $stroke) {
            $stroke->validate();
        }

        $this->stroke = $stroke;
        return $this;
    }

    public function jsonSerialize()
    {
        $properties = $this->getSetProperties();
        $properties['role'] = 'divider';
        return $properties;
    }
}
