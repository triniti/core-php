<?php
declare(strict_types=1);

namespace Triniti\AppleNews\Style;

use Assert\Assertion;
use Triniti\AppleNews\Condition;

/**
 * @link https://developer.apple.com/documentation/applenewsformat/conditionaltextstyle
 */
class ConditionalTextStyle extends TextStyle
{
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
     * @throws \Throwable
     */
    public function setConditions(?array $conditions = []): self
    {
        $this->conditions = [];

        if (!empty($conditions)) {
            foreach ($conditions as $condition) {
                $this->addCondition($condition);
            }
        }

        return $this;
    }

    /**
     * @param Condition|null $condition
     *
     * @return static
     * @throws \Throwable
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
     * @throws \Throwable
     */
    public function addConditions(?array $conditions = []): self
    {
        if (!empty($conditions)) {
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
