<?php
declare(strict_types=1);

namespace Triniti\AppleNews\Layout;

use Triniti\AppleNews\AppleNewsObject;

/**
 * @link https://developer.apple.com/documentation/apple_news/content_inset
 */
class ContentInset extends AppleNewsObject
{
    protected ?bool $bottom = null;
    protected ?bool $left = null;
    protected ?bool $right = null;
    protected ?bool $top = null;

    public function getBottom(): ?bool
    {
        return $this->bottom;
    }

    /**
     * @param bool|null $bottom
     *
     * @return static
     */
    public function setBottom(?bool $bottom = true): self
    {
        $this->bottom = $bottom;

        return $this;
    }

    /**
     * @return bool|null
     */
    public function getLeft()
    {
        return $this->left;
    }

    public function setLeft(?bool $left = true): self
    {
        $this->left = $left;
        return $this;
    }

    public function getRight(): ?bool
    {
        return $this->right;
    }

    public function setRight(?bool $right = true): self
    {
        $this->right = $right;
        return $this;
    }

    public function getTop(): ?bool
    {
        return $this->top;
    }

    public function setTop(?bool $top = true): self
    {
        $this->top = $top;
        return $this;
    }

    public function jsonSerialize()
    {
        return $this->getSetProperties();
    }
}
