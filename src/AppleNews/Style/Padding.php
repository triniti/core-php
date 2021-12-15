<?php
declare(strict_types=1);

namespace Triniti\AppleNews\Style;

use Triniti\AppleNews\AppleNewsObject;
use Triniti\AppleNews\SupportedUnits;

/**
 * @link https://developer.apple.com/documentation/apple_news/padding
 */
class Padding extends AppleNewsObject
{
    /** @var int|SupportedUnits */
    protected $bottom;

    /** @var int|SupportedUnits */
    protected $left;

    /** @var int|SupportedUnits */
    protected $right;

    /** @var int|SupportedUnits */
    protected $top;

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
     * @return int|SupportedUnits
     */
    public function getLeft()
    {
        return $this->left;
    }

    /**
     * @param int|SupportedUnits $left
     *
     * @return static
     */
    public function setLeft($left): self
    {
        $this->left = $left;
        return $this;
    }

    /**
     * @return int|SupportedUnits
     */
    public function getRight()
    {
        return $this->right;
    }

    /**
     * @param int|SupportedUnits $right
     *
     * @return static
     */
    public function setRight($right): self
    {
        $this->right = $right;
        return $this;
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
     * {@inheritdoc}
     */
    public function jsonSerialize(): mixed
    {
        return $this->getSetProperties();
    }
}
