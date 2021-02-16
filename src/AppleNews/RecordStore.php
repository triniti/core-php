<?php
declare(strict_types=1);

namespace Triniti\AppleNews;

use Assert\Assertion;

/**
 * @link https://developer.apple.com/documentation/apple_news/record_store
 */
class RecordStore extends AppleNewsObject
{
    /** @var DataDescriptor[] */
    protected $descriptors = [];

    /** @var \stdClass */
    protected $records;

    /**
     * @return DataDescriptor[]
     */
    public function getDescriptors(): array
    {
        return $this->descriptors;
    }

    /**
     * @param DataDescriptor $descriptor
     *
     * @return static
     */
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

    /**
     * @return \stdClass
     */
    public function getRecords(): ?\stdClass
    {
        return $this->records;
    }

    /**
     * @param \stdClass $records
     *
     * @return static
     */
    public function setRecords(\stdClass $records): self
    {
        $this->records = $records;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        return $this->getSetProperties();
    }

    /**
     * {@inheritdoc}
     */
    public function validate(): void
    {
        Assertion::notNull($this->descriptors);
        Assertion::notNull($this->records);
    }
}
