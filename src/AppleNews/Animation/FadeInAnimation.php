<?php
declare(strict_types=1);

namespace Triniti\AppleNews\Animation;

use Assert\Assertion;

/**
 * @link https://developer.apple.com/documentation/apple_news/fade_in_animation
 */
class FadeInAnimation extends ComponentAnimation
{
    protected ?float $initialAlpha = 0.3;

    public function getInitialAlpha(): ?float
    {
        return $this->initialAlpha;
    }

    public function setInitialAlpha(?float $initialAlpha = null): self
    {
        if (is_float($initialAlpha)) {
            Assertion::lessOrEqualThan($initialAlpha, 1.0);
            Assertion::greaterOrEqualThan($initialAlpha, 0);
        }

        $this->initialAlpha = $initialAlpha;
        return $this;
    }

    public function jsonSerialize()
    {
        $properties = $this->getSetProperties();
        $properties['type'] = 'fade_in';
        return $properties;
    }
}
