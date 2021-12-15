<?php
declare(strict_types=1);

namespace Triniti\AppleNews\Layout;

use Triniti\AppleNews\AppleNewsObject;

/**
 * @link https://developer.apple.com/documentation/apple_news/content_inset
 */
class ContentInset extends AppleNewsObject
{
    /** @var boolean */
    protected $bottom;

    /** @var boolean */
    protected $left;

    /** @var boolean */
    protected $right;

    /** @var boolean */
    protected $top;

    /**
     * @return bool
     */
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

    /**
     * @param bool $left
     *
     * @return static
     */
    public function setLeft(?bool $left = true): self
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
    public function setRight(?bool $right = true): self
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
    public function setTop(?bool $top = true): self
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
