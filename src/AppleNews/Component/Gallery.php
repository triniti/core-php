<?php
declare(strict_types=1);

namespace Triniti\AppleNews\Component;

use Assert\Assertion;

/**
 * @link https://developer.apple.com/documentation/apple_news/gallery
 */
class Gallery extends Component
{
    /** @var GalleryItem[] */
    protected $items = [];

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

    /**
     * @param GalleryItem $item
     *
     * @return static
     */
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

    /**
     * {@inheritdoc}
     */
    public function validate(): void
    {
        Assertion::notEmpty($this->items);
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize(): array
    {
        $properties = $this->getSetProperties();
        $properties['role'] = 'gallery';
        return $properties;
    }
}
