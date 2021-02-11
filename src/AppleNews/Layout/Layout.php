<?php
declare(strict_types=1);

namespace Triniti\AppleNews\Layout;

use Assert\Assertion;
use Triniti\AppleNews\AppleNewsObject;

/**
 * @link https://developer.apple.com/documentation/apple_news/layout
 */
class Layout extends AppleNewsObject
{
    protected ?int $columns = null;
    protected ?int $gutter = null;
    protected ?int $margin = null;
    protected ?int $width = null;

    public function getColumns(): ?int
    {
        return $this->columns;
    }

    public function setColumns(int $columns): self
    {
        Assertion::greaterOrEqualThan($columns, 1, 'You need at least 1 columns');
        $this->columns = $columns;
        return $this;
    }

    public function getGutter(): ?int
    {
        return $this->gutter;
    }

    public function setGutter(?int $gutter = 20): self
    {
        if (is_int($gutter)) {
            Assertion::greaterOrEqualThan($gutter, 0, 'gutter can not be negative');
        }

        $this->gutter = $gutter;
        return $this;
    }

    public function getMargin(): ?int
    {
        return $this->margin;
    }

    public function setMargin(?int $margin = 60): self
    {
        Assertion::greaterOrEqualThan($margin, 0);
        $this->margin = $margin;
        return $this;
    }

    public function getWidth(): ?int
    {
        return $this->width;
    }

    public function setWidth(int $width): self
    {
        Assertion::greaterOrEqualThan($width, 1, 'The width cannot be negative or 0');
        $this->width = $width;
        return $this;
    }

    public function jsonSerialize()
    {
        return $this->getSetProperties();
    }

    public function validate(): void
    {
        Assertion::notNull($this->columns, 'Columns can not be null');
        Assertion::notNull($this->width, 'Width can not be null');

        if (null !== $this->margin) {
            Assertion::true($this->margin < ($this->width / 2), 'Margin can not be greater than or equal to the width/2');
        }
    }
}
