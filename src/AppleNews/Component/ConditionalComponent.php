<?php
declare(strict_types=1);

namespace Triniti\AppleNews\Component;

use Assert\Assertion;
use Triniti\AppleNews\Condition;

/**
 * @link https://developer.apple.com/documentation/apple_news/conditionalcomponent
 */
class ConditionalComponent extends Component
{
    /** @var Condition[] */
    protected $conditions = [];

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
