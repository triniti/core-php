<?php
declare(strict_types=1);

namespace Triniti\AppleNews\Layout;

use Assert\Assertion;
use Triniti\AppleNews\AppleNewsObject;

/**
 * @link https://developer.apple.com/documentation/apple_news/anchor
 */
class Anchor extends AppleNewsObject
{
    /** @var string */
    protected $originAnchorPosition;

    /** @var integer */
    protected $rangeLength;

    /** @var integer */
    protected $rangeStart;

    /** @var string */
    protected $target;

    /** @var string */
    protected $targetAnchorPosition;

    /** @var string */
    protected $targetComponentIdentifier;

    /**
     * @var string[] Valid position values
     */
    private $validPositions = [
        'top',
        'center',
        'bottom',
    ];

    /**
     * @return string
     */
    public function getOriginAnchorPosition(): ?string
    {
        return $this->originAnchorPosition;
    }

    /**
     * @param string $originAnchorPosition
     *
     * @return static
     */
    public function setOriginAnchorPosition(?string $originAnchorPosition = null): self
    {
        if (is_string($originAnchorPosition)) {
            Assertion::inArray($originAnchorPosition, $this->validPositions);
        }

        $this->originAnchorPosition = $originAnchorPosition;
        return $this;
    }

    /**
     * @return bool
     */
    public function hasRangeLength(): bool
    {
        return null !== $this->rangeLength;
    }

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
    public function setRangeLength(?int $rangeLength = null): self
    {
        $this->rangeLength = $rangeLength;
        return $this;
    }

    /**
     * @return bool
     */
    public function hasRangeStart(): bool
    {
        return null !== $this->rangeStart;
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
    public function setRangeStart(?int $rangeStart = null): self
    {
        $this->rangeStart = $rangeStart;
        return $this;
    }

    /**
     * @return string
     */
    public function getTarget(): ?string
    {
        return $this->target;
    }

    /**
     * @param string $target
     *
     * @return static
     */
    public function setTarget(?string $target = null): self
    {
        $this->target = $target;
        return $this;
    }

    /**
     * @return string
     */
    public function getTargetAnchorPosition(): ?string
    {
        return $this->targetAnchorPosition;
    }

    /**
     * @param string $targetAnchorPosition
     *
     * @return static
     */
    public function setTargetAnchorPosition(string $targetAnchorPosition): self
    {
        Assertion::inArray($targetAnchorPosition, $this->validPositions);
        $this->targetAnchorPosition = $targetAnchorPosition;
        return $this;
    }

    /**
     * @return string
     */
    public function getTargetComponentIdentifier(): ?string
    {
        return $this->targetComponentIdentifier;
    }

    /**
     * @param string $targetComponentIdentifier
     *
     * @return static
     */
    public function setTargetComponentIdentifier(?string $targetComponentIdentifier = null): self
    {
        $this->targetComponentIdentifier = $targetComponentIdentifier;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize(): array
    {
        return $this->getSetProperties();
    }

    /**
     * {@inheritdoc}
     */
    public function validate(): void
    {
        Assertion::notNull($this->targetAnchorPosition);

        if ($this->hasRangeLength()) {
            Assertion::true($this->hasRangeStart(), 'if rangeLength is specified, rangeStart is required.');
        }

        if ($this->hasRangeStart()) {
            Assertion::true($this->hasRangeLength(), 'if rangeStart is specified, rangeLength is required.');
        }
    }
}
