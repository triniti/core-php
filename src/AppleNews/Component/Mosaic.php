<?php
declare(strict_types=1);

namespace Triniti\AppleNews\Component;

use Assert\Assertion;

/**
 * @link https://developer.apple.com/documentation/apple_news/mosaic
 */
class Mosaic extends Component
{
    /** @var GalleryItem[] */
    protected array $items = [];

    /**
     * @return GalleryItem[]
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * @param GalleryItem[] $items
     *
     * @return static
     */
    public function setItems(?array $items = []): self
    {
        $this->items = [];

        if (null !== $items) {
            foreach ($items as $item) {
                $this->addItem($item);
            }
        }

        return $this;
    }

    public function addItem(?GalleryItem $item = null): self
    {
        if (null !== $item) {
            $item->validate();
            $this->items[] = $item;
        }

        return $this;
    }

    /**
     * @param GalleryItem[] $items
     *
     * @return static
     */
    public function addItems(?array $items = []): self
    {
        if (null !== $items) {
            foreach ($items as $item) {
                $this->addItem($item);
            }
        }

        return $this;
    }

    public function validate(): void
    {
        Assertion::notEmpty($this->items);
    }

    public function jsonSerialize()
    {
        $properties = $this->getSetProperties();
        $properties['role'] = 'mosaic';
        return $properties;
    }
}
