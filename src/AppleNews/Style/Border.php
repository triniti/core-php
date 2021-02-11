<?php
declare(strict_types=1);

namespace Triniti\AppleNews\Style;

use Triniti\AppleNews\AppleNewsObject;

/**
 * @link https://developer.apple.com/documentation/apple_news/border
 */
class Border extends AppleNewsObject
{
    protected ?StrokeStyle $all = null;
    protected ?bool $bottom = null;
    protected ?bool $left = null;
    protected ?bool $right = null;
    protected ?bool $top = null;

    public function getAll(): ?StrokeStyle
    {
        return $this->all;
    }

    public function setAll(?StrokeStyle $all = null): self
    {
        $this->all = $all;
        return $this;
    }

    public function getBottom(): ?bool
    {
        return $this->bottom;
    }

    public function setBottom(bool $bottom = true): self
    {
        $this->bottom = $bottom;
        return $this;
    }

    public function getLeft(): ?bool
    {
        return $this->left;
    }

    public function setLeft(bool $left = true): self
    {
        $this->left = $left;
        return $this;
    }

    public function getRight(): ?bool
    {
        return $this->right;
    }

    public function setRight(bool $right = true): self
    {
        $this->right = $right;
        return $this;
    }

    public function getTop(): ?bool
    {
        return $this->top;
    }

    public function setTop(bool $top = true): self
    {
        $this->top = $top;
        return $this;
    }

    public function jsonSerialize()
    {
        return $this->getSetProperties();
    }
}
