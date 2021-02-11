<?php
declare(strict_types=1);

namespace Triniti\AppleNews;

use Assert\Assertion;

/**
 * @link https://developer.apple.com/documentation/apple_news/supported_units
 */
class SupportedUnits extends AppleNewsObject
{
    private string $units;

    public function __construct(string $units)
    {
        Assertion::regex($units, '/^\d+(vw|vmin|vmax|vh|dg|dm|cw|gut|pt)?$/');
        $this->units = $units;
    }

    public function jsonSerialize()
    {
        return $this->units;
    }
}
