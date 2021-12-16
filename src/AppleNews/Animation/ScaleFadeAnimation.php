<?php
declare(strict_types=1);

namespace Triniti\AppleNews\Animation;

use Assert\Assertion;

/**
 * @link https://developer.apple.com/documentation/apple_news/scale_fade_animation
 */
class ScaleFadeAnimation extends ComponentAnimation
{
    /** @var float */
    protected $initialAlpha = 0.3;

    /** @var float */
    protected $initialScale = 0.75;

    /**
     * @return float
     */
    public function getInitialAlpha(): ?float
    {
        return $this->initialAlpha;
    }

    /**
     * @param float $initialAlpha
     *
     * @return static
     */
    public function setInitialAlpha(?float $initialAlpha = null): self
    {
        if (is_float($initialAlpha)) {
            Assertion::lessOrEqualThan($initialAlpha, 1.0);
            Assertion::greaterOrEqualThan($initialAlpha, 0.0);
        }

        $this->initialAlpha = $initialAlpha;
        return $this;
    }

    /**
     * @return float
     */
    public function getInitialScale(): ?float
    {
        return $this->initialScale;
    }

    /**
     * @param float $initialScale
     *
     * @return static
     */
    public function setInitialScale(?float $initialScale = null): self
    {
        if (is_float($initialScale)) {
            Assertion::lessOrEqualThan($initialScale, 1.0);
            Assertion::greaterOrEqualThan($initialScale, 0.0);
        }

        $this->initialScale = $initialScale;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize(): array
    {
        $properties = $this->getSetProperties();
        $properties['type'] = 'scale_fade';
        return $properties;
    }
}
