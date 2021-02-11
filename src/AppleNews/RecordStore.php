<?php
declare(strict_types=1);

namespace Triniti\AppleNews;

use Assert\Assertion;

/**
 * @link https://developer.apple.com/documentation/apple_news/record_store
 */
class RecordStore extends AppleNewsObject
{
    protected ?\stdClass $records = null;

    /** @var DataDescriptor[] */
    protected array $descriptors = [];

    /**
     * @return DataDescriptor[]
     */
    public function getDescriptors(): array
    {
        return $this->descriptors;
    }

    public function addDescriptor(?DataDescriptor $descriptor = null): self
    {
        if (null === $descriptor) {
            return $this;
        }

        $descriptor->validate();
        $this->descriptors[] = $descriptor;
        return $this;
    }

    /**
     * @param DataDescriptor[] $descriptors
     *
     * @return static
     */
    public function addDescriptors(array $descriptors = []): self
    {
        foreach ($descriptors as $descriptor) {
            $this->addDescriptor($descriptor);
        }

        return $this;
    }

    /**
     * @param DataDescriptor[] $descriptors
     *
     * @return static
     */
    public function setDescriptors(array $descriptors = []): self
    {
        $this->descriptors = [];
        $this->addDescriptors($descriptors);
        return $this;
    }

    public function getRecords(): ?\stdClass
    {
        return $this->records;
    }

    public function setRecords(\stdClass $records): self
    {
        $this->records = $records;
        return $this;
    }

    public function jsonSerialize()
    {
        return $this->getSetProperties();
    }

    public function validate(): void
    {
        Assertion::notNull($this->descriptors);
        Assertion::notNull($this->records);
    }
}
