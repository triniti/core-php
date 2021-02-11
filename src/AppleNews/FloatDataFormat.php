<?php
declare(strict_types=1);

namespace Triniti\AppleNews;

use Assert\Assertion;

/**
 * @link https://developer.apple.com/documentation/apple_news/float_data_format
 */
class FloatDataFormat extends DataFormat
{
    protected ?int $decimals = null;

    public function __construct(?int $decimals = null)
    {
        $this->setDecimals($decimals);
    }

    public function getDecimals(): ?int
    {
        return $this->decimals;
    }

    public function setDecimals(?int $decimals = null): self
    {
        if (null === $decimals) {
            $this->decimals = null;
            return $this;
        }

        Assertion::greaterOrEqualThan($decimals, 0, 'Decimal number should be greater than 0');
        $this->decimals = $decimals;
        return $this;
    }

    public function jsonSerialize()
    {
        $properties = $this->getSetProperties();
        $properties['type'] = 'float';
        return $properties;
    }
}
