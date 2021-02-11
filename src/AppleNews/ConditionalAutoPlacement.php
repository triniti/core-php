<?php
declare(strict_types=1);

namespace Triniti\AppleNews;

use Assert\Assertion;
use Triniti\AppleNews\Layout\AutoPlacementLayout;

/**
 * @link https://developer.apple.com/documentation/apple_news/conditionalautoplacement
 */
class ConditionalAutoPlacement extends AppleNewsObject
{
    protected bool $enabled = false;
    protected ?AutoPlacementLayout $layout = null;

    /** @var Condition[] */
    protected array $conditions = [];

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

    public function getEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled = false): self
    {
        $this->enabled = $enabled;
        return $this;
    }

    public function getLayout(): ?AutoPlacementLayout
    {
        return $this->layout;
    }

    public function setLayout(?AutoPlacementLayout $layout = null): self
    {
        if ($layout) {
            $layout->validate();
        }

        $this->layout = $layout;
        return $this;
    }

    public function jsonSerialize()
    {
        return $this->getSetProperties();
    }

    public function validate(): void
    {
        Assertion::notEmpty($this->conditions);
    }
}
