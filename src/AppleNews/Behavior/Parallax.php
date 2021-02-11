<?php
declare(strict_types=1);

namespace Triniti\AppleNews\Behavior;

use Assert\Assertion;

/**
 * @link https://developer.apple.com/documentation/apple_news/parallax
 */
class Parallax extends Behavior
{
    protected ?float $factor = 0.9;

    public function getFactor(): ?float
    {
        return $this->factor;
    }

    public function setFactor(?float $factor = null): self
    {
        if (is_float($factor)) {
            Assertion::lessOrEqualThan($factor, 2.0);
            Assertion::greaterOrEqualThan($factor, 0.5);
        }

        $this->factor = $factor;
        return $this;
    }

    public function jsonSerialize()
    {
        $properties = $this->getSetProperties();
        $properties['type'] = 'parallax';
        return $properties;
    }
}

