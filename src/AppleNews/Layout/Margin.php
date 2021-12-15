<?php
declare(strict_types=1);

namespace Triniti\AppleNews\Layout;

use Triniti\AppleNews\AppleNewsObject;
use Triniti\AppleNews\SupportedUnits;

/**
 * @link https://developer.apple.com/documentation/apple_news/margin
 */
class Margin extends AppleNewsObject
{
    /** @var int|SupportedUnits */
    protected $top;

    /** @var int|SupportedUnits */
    protected $bottom;

    /**
     * @param int|SupportedUnits $margin
     */
    public function __construct($margin = null)
    {
        $this->bottom = $margin;
        $this->top = $margin;
    }

    /**
     * @return int|SupportedUnits
     */
    public function getTop()
    {
        return $this->top;
    }

    /**
     * @param int|SupportedUnits $top
     *
     * @return static
     */
    public function setTop($top): self
    {
        $this->top = $top;
        return $this;
    }

    /**
     * @return int|SupportedUnits
     */
    public function getBottom()
    {
        return $this->bottom;
    }

    /**
     * @param int|SupportedUnits $bottom
     *
     * @return static
     */
    public function setBottom($bottom): self
    {
        $this->bottom = $bottom;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize(): mixed
    {
        return $this->getSetProperties();
    }
}
