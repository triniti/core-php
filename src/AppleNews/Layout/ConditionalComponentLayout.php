<?php
declare(strict_types=1);

namespace Triniti\AppleNews\Layout;

use Assert\Assertion;
use Triniti\AppleNews\Condition;

/**
 * @link https://developer.apple.com/documentation/apple_news/conditionalcomponentlayout
 */
class ConditionalComponentLayout extends ComponentLayout
{
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

    public function validate(): void
    {
        Assertion::notEmpty($this->conditions);
    }
}
