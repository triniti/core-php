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
    protected ?string $backgroundColor = null;
    protected ?Border $border = null;
    protected ?Fill $fill = null;
    protected float $opacity = 1;
    protected ?TableStyle $tableStyle = null;

    public function getBorder(): ?Border
    {
        return $this->border;
    }

    public function setBorder(?Border $border = null): self
    {
        $this->border = $border;
        return $this;
    }

    public function getBackgroundColor(): ?string
    {
        return $this->backgroundColor;
    }

    public function setBackgroundColor(?string $backgroundColor = null): self
    {
        $this->backgroundColor = $backgroundColor;
        return $this;
    }

    public function getFill(): ?Fill
    {
        return $this->fill;
    }

    public function setFill(?Fill $fill = null): self
    {
        if (null !== $fill) {
            $fill->validate();
        }

        $this->fill = $fill;
        return $this;
    }

    public function getOpacity(): float
    {
        return $this->opacity;
    }

    public function setOpacity(float $opacity = 1): self
    {
        Assertion::greaterOrEqualThan($opacity, 0);
        Assertion::lessOrEqualThan($opacity, 1);
        $this->opacity = $opacity;
        return $this;
    }

    public function getTableStyle(): ?TableStyle
    {
        return $this->tableStyle;
    }

    public function setTableStyle(?TableStyle $tableStyle = null): self
    {
        $this->tableStyle = $tableStyle;
        return $this;
    }

    public function jsonSerialize()
    {
        return $this->getSetProperties();
    }
}
