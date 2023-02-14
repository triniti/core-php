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
    /** @var Anchor */
    protected $anchor;

    /** @var ComponentAnimation */
    protected $animation;

    /** @var Behavior */
    protected $behavior;

    /** @var string */
    protected $identifier;

    /** @var string|ComponentLayout */
    protected $layout;

    /** @var string|ComponentStyle */
    protected $style;

    /** @var boolean */
    protected $hidden;

    /** @var ConditionalComponent[] */
    protected $conditional;

    /**
     * @return Anchor
     */
    public function getAnchor(): ?Anchor
    {
        return $this->anchor;
    }

    /**
     * @param Anchor $anchor
     *
     * @return static
     */
    public function setAnchor(?Anchor $anchor = null): self
    {
        if (null !== $anchor) {
            $anchor->validate();
        }

        $this->anchor = $anchor;
        return $this;
    }

    /**
     * @return ComponentAnimation
     */
    public function getAnimation(): ?ComponentAnimation
    {
        return $this->animation;
    }

    /**
     * @param ComponentAnimation $animation
     *
     * @return static
     */
    public function setAnimation(?ComponentAnimation $animation = null): self
    {
        if (null !== $animation) {
            $animation->validate();
        }

        $this->animation = $animation;
        return $this;
    }

    /**
     * @return Behavior
     */
    public function getBehavior(): ?Behavior
    {
        return $this->behavior;
    }

    /**
     * @param Behavior $behavior
     *
     * @return static
     */
    public function setBehavior(?Behavior $behavior = null): self
    {
        if (null !== $behavior) {
            $behavior->validate();
        }

        $this->behavior = $behavior;
        return $this;
    }

    /**
     * @return string
     */
    public function getIdentifier(): ?string
    {
        return $this->identifier;
    }

    /**
     * @param string $identifier
     *
     * @return static
     */
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

    /**
     * @param bool|null $hidden
     *
     * @return static
     */
    public function setHidden(?bool $hidden = true): self
    {
        $this->hidden = $hidden;

        return $this;
    }

    /**
     * @return ConditionalComponent[]
     */
    public function getConditional(): array
    {
        return $this->conditional;
    }

    /**
     * @param ConditionalComponent[] $conditionals
     *
     * @return static
     */
    public function setConditional(?array $conditionals = []): self
    {
        $this->conditional = [];

        if (null !== $conditionals) {
            foreach ($conditionals as $conditional) {
                $this->addConditional($conditional);
            }
        }

        return $this;
    }

    /**
     * @param ConditionalComponent $conditional
     *
     * @return static
     */
    public function addConditional(?ConditionalComponent $conditional = null): self
    {
        if (null !== $conditional) {
            $conditional->validate();
            $this->conditional[] = $conditional;
        }

        return $this;
    }

    /**
     * @param ConditionalComponent[] $conditionals
     *
     * @return static
     */
    public function addConditionals(?array $conditionals = []): self
    {
        if (null !== $conditionals) {
            foreach ($conditionals as $conditional) {
                $this->addConditional($conditional);
            }
        }

        return $this;
    }
}
