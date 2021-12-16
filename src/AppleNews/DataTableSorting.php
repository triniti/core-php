<?php
declare(strict_types=1);

namespace Triniti\AppleNews;

use Assert\Assertion;

/**
 * @link https://developer.apple.com/documentation/apple_news/data_table_sorting
 */
class DataTableSorting extends AppleNewsObject
{
    /**
     * The identifier property of one of the table’s data descriptors
     *
     * @var string
     */
    protected $descriptor;

    /** @var string */
    protected $direction;

    /**
     * @return string
     */
    public function getDescriptor(): ?string
    {
        return $this->descriptor;
    }

    /**
     * @param string $descriptor
     *
     * @return static
     */
    public function setDescriptor(string $descriptor): self
    {
        $this->descriptor = $descriptor;
        return $this;
    }

    /**
     * @return string
     */
    public function getDirection(): ?string
    {
        return $this->direction;
    }

    /**
     * @param string $direction
     *
     * @return static
     */
    public function setDirection(string $direction): self
    {
        Assertion::inArray($direction, ['ascending', 'descending']);
        $this->direction = $direction;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize(): array
    {
        return $this->getSetProperties();
    }

    /**
     * {@inheritdoc}
     */
    public function validate(): void
    {
        Assertion::notNull($this->descriptor);
        Assertion::notNull($this->direction);
    }
}
