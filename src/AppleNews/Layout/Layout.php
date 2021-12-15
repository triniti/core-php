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
    /** @var int */
    protected $columns;

    /** @var int */
    protected $gutter;

    /** @var int */
    protected $margin;

    /** @var int */
    protected $width;

    /**
     * @return int
     */
    public function getColumns(): ?int
    {
        return $this->columns;
    }

    /**
     * @param int $columns
     *
     * @return static
     */
    public function setColumns(int $columns): self
    {
        Assertion::greaterOrEqualThan($columns, 1, 'You need at least 1 columns');
        $this->columns = $columns;
        return $this;
    }

    /**
     * @return int
     */
    public function getGutter(): ?int
    {
        return $this->gutter;
    }

    /**
     * @param int $gutter
     *
     * @return static
     */
    public function setGutter(?int $gutter = 20): self
    {
        if (is_int($gutter)) {
            Assertion::greaterOrEqualThan($gutter, 0, 'gutter can not be negative');
        }

        $this->gutter = $gutter;
        return $this;
    }

    /**
     * @return int
     */
    public function getMargin(): ?int
    {
        return $this->margin;
    }

    /**
     * @param int $margin
     *
     * @return static
     */
    public function setMargin(?int $margin = 60): self
    {
        Assertion::greaterOrEqualThan($margin, 0);
        $this->margin = $margin;
        return $this;
    }

    /**
     * @return int
     */
    public function getWidth(): ?int
    {
        return $this->width;
    }

    /**
     * @param int $width
     *
     * @return static
     */
    public function setWidth(int $width): self
    {
        Assertion::greaterOrEqualThan($width, 1, 'The width cannot be negative or 0');
        $this->width = $width;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize(): mixed
    {
        $properties = $this->getSetProperties();
        return $properties;
    }

    /**
     * {@inheritdoc}
     */
    public function validate(): void
    {
        Assertion::notNull($this->columns, 'Columns can not be null');
        Assertion::notNull($this->width, 'Width can not be null');

        if (null !== $this->margin) {
            Assertion::true($this->margin < ($this->width / 2), 'Margin can not be greater than or equal to the width/2');
        }
    }
}
