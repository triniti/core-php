<?php
declare(strict_types=1);

namespace Triniti\AppleNews\Component;

use Assert\Assertion;
use Triniti\AppleNews\Animation\ComponentAnimation;
use Triniti\AppleNews\AppleNewsObject;
use Triniti\AppleNews\Behavior\Behavior;
use Triniti\AppleNews\Layout\Anchor;
use Triniti\AppleNews\Layout\ComponentLayout;
use Triniti\AppleNews\Style\ComponentStyle;

/**
 * @link https://developer.apple.com/documentation/apple_news/component
 */
abstract class Component extends AppleNewsObject
{
    protected ?Anchor $anchor = null;
    protected ?ComponentAnimation $animation = null;
    protected ?Behavior $behavior = null;
    protected ?string $identifier = null;

    /** @var string|ComponentLayout */
    protected $layout;

    /** @var string|ComponentStyle */
    protected $style;

    public function getAnchor(): ?Anchor
    {
        return $this->anchor;
    }

    public function setAnchor(?Anchor $anchor = null): self
    {
        if (null !== $anchor) {
            $anchor->validate();
        }

        $this->anchor = $anchor;
        return $this;
    }

    public function getAnimation(): ?ComponentAnimation
    {
        return $this->animation;
    }

    public function setAnimation(?ComponentAnimation $animation = null): self
    {
        if (null !== $animation) {
            $animation->validate();
        }

        $this->animation = $animation;
        return $this;
    }

    public function getBehavior(): ?Behavior
    {
        return $this->behavior;
    }

    public function setBehavior(?Behavior $behavior = null): self
    {
        if (null !== $behavior) {
            $behavior->validate();
        }

        $this->behavior = $behavior;
        return $this;
    }

    public function getIdentifier(): ?string
    {
        return $this->identifier;
    }

    public function setIdentifier(?string $identifier = null): self
    {
        $this->identifier = $identifier;
        return $this;
    }

    /**
     * @return string|ComponentLayout
     */
    public function getLayout()
    {
        return $this->layout;
    }

    /**
     * @param string|ComponentLayout $layout
     *
     * @return static
     */
    public function setLayout($layout = null): self
    {
        if (!is_string($layout) && (null !== $layout) && !($layout instanceof ComponentLayout)) {
            Assertion::true(
                false,
                'layout must be a string or instance of ComponentLayout.'
            );
        }

        if ($layout instanceof ComponentLayout) {
            $layout->validate();
        }

        $this->layout = $layout;
        return $this;
    }

    /**
     * @return string|ComponentStyle
     */
    public function getStyle()
    {
        return $this->style;
    }

    /**
     * @param string|ComponentStyle $style
     *
     * @return static
     */
    public function setStyle($style = null): self
    {
        if (!is_string($style) && (null !== $style) && !($style instanceof ComponentStyle)) {
            Assertion::true(
                false,
                'style must be a string or instance of ComponentStyle.'
            );
        }

        if ($style instanceof ComponentStyle) {
            $style->validate();
        }

        $this->style = $style;
        return $this;
    }
}
