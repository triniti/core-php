<?php
declare(strict_types=1);

namespace Triniti\AppleNews\Style;

use Assert\Assertion;
use Triniti\AppleNews\AppleNewsObject;

/**
 * @link https://developer.apple.com/documentation/apple_news/component_style
 */
class ComponentStyle extends AppleNewsObject
{
    /** @var ConditionalComponentStyle[] */
    protected $conditional;

    /** @var string */
    protected $backgroundColor;

    /** @var Border */
    protected $border;

    /** @var Fill */
    protected $fill;

    /** @var float */
    protected $opacity = 1;

    /** @var TableStyle */
    protected $tableStyle;

    /**
     * @return ConditionalComponentStyle[]
     */
    public function getConditional(): array
    {
        return $this->conditional;
    }

    /**
     * @param ConditionalComponentStyle[] $conditionals
     *
     * @return static
     * @throws \Throwable
     */
    public function setConditional(?array $conditionals = []): self
    {
        $this->conditional = [];

        if (!empty($conditionals)) {
            foreach ($conditionals as $conditional) {
                $this->addConditional($conditional);
            }
        }

        return $this;
    }

    /**
     * @param ConditionalComponentStyle|null $conditional
     *
     * @return static
     * @throws \Throwable
     */
    public function addConditional(?ConditionalComponentStyle $conditional = null): self
    {
        if (null !== $conditional) {
            $conditional->validate();
            $this->conditional[] = $conditional;
        }

        return $this;
    }

    /**
     * @param ConditionalComponentStyle[] $conditionals
     *
     * @return static
     * @throws \Throwable
     */
    public function addConditionals(?array $conditionals = []): self
    {
        if (!empty($conditionals)) {
            foreach ($conditionals as $conditional) {
                $this->addConditional($conditional);
            }
        }

        return $this;
    }

    /**
     * @return Border
     */
    public function getBorder(): ?Border
    {
        return $this->border;
    }

    /**
     * @param Border $border
     *
     * @return static
     */
    public function setBorder(?Border $border = null): self
    {
        $this->border = $border;
        return $this;
    }

    /**
     * @return string
     */
    public function getBackgroundColor(): ?string
    {
        return $this->backgroundColor;
    }

    /**
     * @param string $backgroundColor
     *
     * @return static
     */
    public function setBackgroundColor(?string $backgroundColor = null): self
    {
        $this->backgroundColor = $backgroundColor;
        return $this;
    }

    /**
     * @return Fill
     */
    public function getFill(): ?Fill
    {
        return $this->fill;
    }

    /**
     * @param Fill $fill
     *
     * @return static
     */
    public function setFill(?Fill $fill = null): self
    {
        if (null !== $fill) {
            $fill->validate();
        }

        $this->fill = $fill;
        return $this;
    }

    /**
     * @return float
     */
    public function getOpacity(): float
    {
        return $this->opacity;
    }

    /**
     * @param float $opacity
     *
     * @return static
     */
    public function setOpacity(float $opacity = 1): self
    {
        Assertion::greaterOrEqualThan($opacity, 0);
        Assertion::lessOrEqualThan($opacity, 1);
        $this->opacity = $opacity;
        return $this;
    }

    /**
     * @return TableStyle
     */
    public function getTableStyle(): ?TableStyle
    {
        return $this->tableStyle;
    }

    /**
     * @param TableStyle $tableStyle
     *
     * @return static
     */
    public function setTableStyle(?TableStyle $tableStyle = null): self
    {
        $this->tableStyle = $tableStyle;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize(): array
    {
        return $this->getSetProperties();
    }
}
