<?php
declare(strict_types=1);

namespace Triniti\AppleNews\Animation;

use Assert\Assertion;

/**
 * @link https://developer.apple.com/documentation/apple_news/scale_fade_animation
 */
class ScaleFadeAnimation extends ComponentAnimation
{
    protected ?float $initialAlpha = 0.3;
    protected ?float $initialScale = 0.75;

    public function getInitialAlpha(): ?float
    {
        return $this->initialAlpha;
    }

    public function setInitialAlpha(?float $initialAlpha = null): self
    {
        if (is_float($initialAlpha)) {
            Assertion::lessOrEqualThan($initialAlpha, 1.0);
            Assertion::greaterOrEqualThan($initialAlpha, 0.0);
        }

        $this->initialAlpha = $initialAlpha;
        return $this;
    }

    public function getInitialScale(): ?float
    {
        return $this->initialScale;
    }

    public function setInitialScale(?float $initialScale = null): self
    {
        if (is_float($initialScale)) {
            Assertion::lessOrEqualThan($initialScale, 1.0);
            Assertion::greaterOrEqualThan($initialScale, 0.0);
        }

        $this->initialScale = $initialScale;
        return $this;
    }

    public function jsonSerialize()
    {
        $properties = $this->getSetProperties();
        $properties['type'] = 'scale_fade';
        return $properties;
    }
}
