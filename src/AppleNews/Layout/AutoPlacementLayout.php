<?php
declare(strict_types=1);

namespace Triniti\AppleNews\Layout;

use Triniti\AppleNews\AppleNewsObject;

/**
 * @link https://developer.apple.com/documentation/apple_news/autoplacementlayout
 */
class AutoPlacementLayout extends AppleNewsObject
{
    /** @var int|Margin */
    protected $margin;

    /**
     * @return int|Margin
     */
    public function getMargin()
    {
        return $this->margin;
    }

    /**
     * @param int|Margin $margin
     *
     * @return static
     */
    public function setMargin($margin): self
    {
        $this->margin = $margin;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        return $this->getSetProperties();
    }
}
