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
    protected ?string $originAnchorPosition = null;
    protected ?int $rangeLength = null;
    protected ?int $rangeStart = null;
    protected ?string $target = null;
    protected ?string $targetAnchorPosition = null;
    protected ?string $targetComponentIdentifier = null;

    /**
     * @var string[] Valid position values
     */
    private array $validPositions = [
        'top',
        'center',
        'bottom',
    ];

    public function getOriginAnchorPosition(): ?string
    {
        return $this->originAnchorPosition;
    }

    public function setOriginAnchorPosition(?string $originAnchorPosition = null): self
    {
        if (is_string($originAnchorPosition)) {
            Assertion::inArray($originAnchorPosition, $this->validPositions);
        }

        $this->originAnchorPosition = $originAnchorPosition;
        return $this;
    }

    public function hasRangeLength(): bool
    {
        return null !== $this->rangeLength;
    }

    public function getRangeLength(): ?int
    {
        return $this->rangeLength;
    }

    public function setRangeLength(?int $rangeLength = null): self
    {
        $this->rangeLength = $rangeLength;
        return $this;
    }

    public function hasRangeStart(): bool
    {
        return null !== $this->rangeStart;
    }

    public function getRangeStart(): ?int
    {
        return $this->rangeStart;
    }

    public function setRangeStart(?int $rangeStart = null): self
    {
        $this->rangeStart = $rangeStart;
        return $this;
    }
    public function getTarget(): ?string
    {
        return $this->target;
    }

    public function setTarget(?string $target = null): self
    {
        $this->target = $target;
        return $this;
    }

    public function getTargetAnchorPosition(): ?string
    {
        return $this->targetAnchorPosition;
    }

    public function setTargetAnchorPosition(string $targetAnchorPosition): self
    {
        Assertion::inArray($targetAnchorPosition, $this->validPositions);
        $this->targetAnchorPosition = $targetAnchorPosition;
        return $this;
    }

    public function getTargetComponentIdentifier(): ?string
    {
        return $this->targetComponentIdentifier;
    }

    public function setTargetComponentIdentifier(?string $targetComponentIdentifier = null): self
    {
        $this->targetComponentIdentifier = $targetComponentIdentifier;
        return $this;
    }

    public function jsonSerialize()
    {
        return $this->getSetProperties();
    }

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
