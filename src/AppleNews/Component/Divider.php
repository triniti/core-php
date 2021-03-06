<?php
declare(strict_types=1);

namespace Triniti\AppleNews\Component;

use Triniti\AppleNews\Style\StrokeStyle;

/**
 * @link https://developer.apple.com/documentation/apple_news/divider
 */
class Divider extends Component
{
    /** @var StrokeStyle */
    protected $stroke;

    /**
     * @return StrokeStyle
     */
    public function getStroke(): ?StrokeStyle
    {
        return $this->stroke;
    }

    /**
     * @param StrokeStyle $stroke
     *
     * @return static
     */
    public function setStroke(?StrokeStyle $stroke = null): self
    {
        if (null !== $stroke) {
            $stroke->validate();
        }

        $this->stroke = $stroke;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        $properties = $this->getSetProperties();
        $properties['role'] = 'divider';
        return $properties;
    }
}
