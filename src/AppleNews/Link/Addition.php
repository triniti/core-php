<?php
declare(strict_types=1);

namespace Triniti\AppleNews\Link;

use Assert\Assertion;
use Triniti\AppleNews\AppleNewsObject;

abstract class Addition extends AppleNewsObject
{
    protected ?int $rangeLength = null;
    protected ?int $rangeStart = null;

    public function getRangeLength(): ?int
    {
        return $this->rangeLength;
    }

    public function setRangeLength(?int $rangeLength)
    {
        $this->rangeLength = $rangeLength;
        return $this;
    }

    public function getRangeStart(): ?int
    {
        return $this->rangeStart;
    }

    public function setRangeStart(?int $rangeStart)
    {
        $this->rangeStart = $rangeStart;
        return $this;
    }

    public function validate(): void
    {
        Assertion::notNull($this->rangeLength, 'rangeLength is required');
        Assertion::notNull($this->rangeStart, 'rangeStart is required');
    }
}
