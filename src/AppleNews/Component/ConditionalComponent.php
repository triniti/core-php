<?php
declare(strict_types=1);

namespace Triniti\AppleNews\Component;

use Assert\Assertion;
use Triniti\AppleNews\AppleNewsObject;
use Triniti\AppleNews\Condition;
use Triniti\AppleNews\Animation\ComponentAnimation;
use Triniti\AppleNews\Behavior\Behavior;
use Triniti\AppleNews\Layout\Anchor;
use Triniti\AppleNews\Layout\ComponentLayout;
use Triniti\AppleNews\Style\ComponentStyle;

/**
 * @link https://developer.apple.com/documentation/apple_news/conditionalcomponent
 */
class ConditionalComponent extends AppleNewsObject
{
    /** @var Anchor */
    protected $anchor;

    /** @var ComponentAnimation */
    protected $animation;

    /** @var Behavior */
    protected $behavior;

    /** @var Condition[] */
    protected $conditions = [];

    /** @var boolean */
    protected $hidden;

    /** @var string|ComponentLayout */
    protected $layout;

    /** @var string|ComponentStyle */
    protected $style;

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
     * @return Condition[]
     */
    public function getConditions(): array
    {
        return $this->conditions;
    }

    /**
     * @param Condition[] $conditions
     *
     * @return static
     */
    public function setConditions(?array $conditions = []): self
    {
        $this->conditions = [];

        if (null !== $conditions) {
            foreach ($conditions as $condition) {
                $this->addCondition($condition);
            }
        }

        return $this;
    }

    /**
     * @param Condition $condition
     *
     * @return static
     */
    public function addCondition(?Condition $condition = null): self
    {
        if (null !== $condition) {
            $condition->validate();
            $this->conditions[] = $condition;
        }

        return $this;
    }

    /**
     * @param Condition[] $conditions
     *
     * @return static
     */
    public function addConditions(?array $conditions = []): self
    {
        if (null !== $conditions) {
            foreach ($conditions as $condition) {
                $this->addCondition($condition);
            }
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getHidden(): ?bool
    {
        return $this->hidden;
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
     * {@inheritdoc}
     */
    public function validate(): void
    {
        Assertion::notEmpty($this->conditions);
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize(): array
    {
        $properties = $this->getSetProperties();
        return $properties;
    }
}
