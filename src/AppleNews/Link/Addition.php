<?php
declare(strict_types=1);

namespace Triniti\AppleNews\Link;

use Assert\Assertion;
use Triniti\AppleNews\AppleNewsObject;

abstract class Addition extends AppleNewsObject
{
    /** @var int $rangeLength */
    protected $rangeLength;

    /** @var int $rangeStart */
    protected $rangeStart;

    /**
     * @return int
     */
    public function getRangeLength(): ?int
    {
        return $this->rangeLength;
    }

    /**
     * @param int $rangeLength
     *
     * @return static
     */
    public function setRangeLength(?int $rangeLength)
    {
        $this->rangeLength = $rangeLength;
        return $this;
    }

    /**
     * @return int
     */
    public function getRangeStart(): ?int
    {
        return $this->rangeStart;
    }

    /**
     * @param int $rangeStart
     *
     * @return static
     */
    public function setRangeStart(?int $rangeStart)
    {
        $this->rangeStart = $rangeStart;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function validate(): void
    {
        Assertion::notNull($this->rangeLength, 'rangeLength is required');
        Assertion::notNull($this->rangeStart, 'rangeStart is required');
    }
}
