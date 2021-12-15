<?php
declare(strict_types=1);

namespace Triniti\AppleNews\Component;

use Triniti\AppleNews\CollectionDisplay;
use Triniti\AppleNews\HorizontalStackDisplay;
use Triniti\AppleNews\Link\ComponentLink;

/**
 * @link https://developer.apple.com/documentation/apple_news/container
 */
class Container extends Component
{
    /** @var ComponentLink[] */
    protected $additions = [];

    /** @var Component[] */
    protected $components = [];

    /** @var CollectionDisplay|HorizontalStackDisplay */
    protected $contentDisplay;

    /**
     * @return ComponentLink[]
     */
    public function getAdditions(): array
    {
        return $this->additions;
    }

    /**
     * @param ComponentLink[] $additions
     *
     * @return static
     */
    public function setAdditions(?array $additions = []): self
    {
        $this->additions = [];

        if (null !== $additions) {
            foreach ($additions as $addition) {
                $this->addAddition($addition);
            }
        }

        return $this;
    }

    /**
     * @param ComponentLink $addition
     *
     * @return static
     */
    public function addAddition(?ComponentLink $addition = null): self
    {
        if (null !== $addition) {
            $addition->validate();
            $this->additions[] = $addition;
        }

        return $this;
    }

    /**
     * @param ComponentLink[] $additions
     *
     * @return static
     */
    public function addAdditions(?array $additions = []): self
    {
        if (null !== $additions) {
            foreach ($additions as $addition) {
                $this->addAddition($addition);
            }
        }

        return $this;
    }

    /**
     * @return Component[]
     */
    public function getComponents(): array
    {
        return $this->components;
    }

    /**
     * @param ComponentLink[] $components
     *
     * @return static
     */
    public function setComponents(?array $components = []): self
    {
        $this->components = [];

        if (null !== $components) {
            foreach ($components as $component) {
                $this->addComponent($component);
            }
        }

        return $this;
    }

    /**
     * @param Component $component
     *
     * @return static
     */
    public function addComponent(?Component $component = null): self
    {
        if (null !== $component) {
            $component->validate();
            $this->components[] = $component;
        }

        return $this;
    }

    /**
     * @param Component[] $components
     *
     * @return static
     */
    public function addComponents(?array $components = []): self
    {
        if (null !== $components) {
            foreach ($components as $component) {
                $this->addComponent($component);
            }
        }

        return $this;
    }

    /**
     * @return CollectionDisplay|HorizontalStackDisplay
     */
    public function getContentDisplay()
    {
        return $this->contentDisplay;
    }

    /**
     * @param CollectionDisplay|HorizontalStackDisplay $display
     *
     * @return static
     */
    public function setContentDisplay($display = null): self
    {
        if ($display instanceof CollectionDisplay) {
            $display->validate();
        }

        $this->contentDisplay = $display;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize(): mixed
    {
        $properties = $this->getSetProperties();
        $properties['role'] = 'container';
        return $properties;
    }
}
