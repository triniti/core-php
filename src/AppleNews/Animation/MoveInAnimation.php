<?php
declare(strict_types=1);

namespace Triniti\AppleNews\Animation;

use Assert\Assertion;

/**
 * @link https://developer.apple.com/documentation/apple_news/move_in_animation
 */
class MoveInAnimation extends ComponentAnimation
{
    /** @var string */
    protected $preferredStartingPosition;

    /**
     * @return string
     */
    public function getPreferredStartingPosition(): ?string
    {
        return $this->preferredStartingPosition;
    }

    /**
     * @param string $preferredStartingPosition
     *
     * @return static
     */
    public function setPreferredStartingPosition(?string $preferredStartingPosition = null): self
    {
        if (is_string($preferredStartingPosition)) {
            Assertion::inArray($preferredStartingPosition, ['left', 'right']);
        }

        $this->preferredStartingPosition = $preferredStartingPosition;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize(): array
    {
        $properties = $this->getSetProperties();
        $properties['type'] = 'move_in';
        return $properties;
    }
}
