<?php
declare(strict_types=1);

namespace Triniti\AppleNews\Style;

use Triniti\AppleNews\AppleNewsObject;

/**
 * @link https://developer.apple.com/documentation/apple_news/border
 */
class Border extends AppleNewsObject
{
    /** @var StrokeStyle */
    protected $all;

    /** @var bool */
    protected $bottom;

    /** @var bool */
    protected $left;

    /** @var bool */
    protected $right;

    /** @var bool */
    protected $top;

    /**
     * @return StrokeStyle
     */
    public function getAll(): ?StrokeStyle
    {
        return $this->all;
    }

    /**
     * @param StrokeStyle $all
     *
     * @return static
     */
    public function setAll(?StrokeStyle $all = null): self
    {
        $this->all = $all;
        return $this;
    }

    /**
     * @return bool
     */
    public function getBottom(): ?bool
    {
        return $this->bottom;
    }

    /**
     * @param bool $bottom
     *
     * @return static
     */
    public function setBottom(bool $bottom = true): self
    {
        $this->bottom = $bottom;
        return $this;
    }

    /**
     * @return bool
     */
    public function getLeft(): ?bool
    {
        return $this->left;
    }

    /**
     * @param bool $left
     *
     * @return static
     */
    public function setLeft(bool $left = true): self
    {
        $this->left = $left;
        return $this;
    }

    /**
     * @return bool
     */
    public function getRight(): ?bool
    {
        return $this->right;
    }

    /**
     * @param bool $right
     *
     * @return static
     */
    public function setRight(bool $right = true): self
    {
        $this->right = $right;
        return $this;
    }

    /**
     * @return bool
     */
    public function getTop(): ?bool
    {
        return $this->top;
    }

    /**
     * @param bool $top
     *
     * @return static
     */
    public function setTop(bool $top = true): self
    {
        $this->top = $top;
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
