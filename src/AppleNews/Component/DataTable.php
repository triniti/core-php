<?php
declare(strict_types=1);

namespace Triniti\AppleNews\Component;

use Assert\Assertion;
use Triniti\AppleNews\DataTableSorting;
use Triniti\AppleNews\RecordStore;

/**
 * @link https://developer.apple.com/documentation/apple_news/data_table
 */
class DataTable extends Component
{
    /** @var RecordStore */
    protected $data;

    /** @var string */
    protected $dataOrientation;

    /** @var bool */
    protected $showDescriptorLabels;

    /** @var DataTableSorting[] */
    protected $sortBy = [];

    /**
     * @return RecordStore
     */
    public function getData(): ?RecordStore
    {
        return $this->data;
    }

    /**
     * @param RecordStore $data
     *
     * @return static
     */
    public function setData(?RecordStore $data = null): self
    {
        if (null !== $data) {
            $data->validate();
        }

        $this->data = $data;
        return $this;
    }

    /**
     * @return string
     */
    public function getDataOrientation(): ?string
    {
        return $this->dataOrientation;
    }

    /**
     * @param string $dataOrientation
     *
     * @return static
     */
    public function setDataOrientation(?string $dataOrientation = 'horizontal'): self
    {
        if (null === $dataOrientation) {
            $dataOrientation = 'horizontal';
        }

        Assertion::inArray(
            $dataOrientation,
            ['horizontal', 'vertical'],
            'Data Orientation must be one of the following values: horizontal, vertical.'
        );

        $this->dataOrientation = $dataOrientation;
        return $this;
    }

    /**
     * @return bool
     */
    public function getShowDescriptorLabels(): ?bool
    {
        return $this->showDescriptorLabels;
    }

    /**
     * @param bool $showDescriptorLabels
     *
     * @return static
     */
    public function setShowDescriptorLabels(?bool $showDescriptorLabels = true): self
    {
        if (null === $showDescriptorLabels) {
            $showDescriptorLabels = true;
        }

        $this->showDescriptorLabels = $showDescriptorLabels;
        return $this;
    }

    /**
     * @return DataTableSorting[]
     */
    public function getSortBys(): array
    {
        return $this->sortBy;
    }

    /**
     * @param DataTableSorting[] $sortBys
     *
     * @return static
     */
    public function setSortBys(?array $sortBys = []): self
    {
        $this->sortBy = [];

        if (null !== $sortBys) {
            foreach ($sortBys as $sortBy) {
                $this->addSortBy($sortBy);
            }
        }

        return $this;
    }

    /**
     * @param DataTableSorting $sortBy
     *
     * @return static
     */
    public function addSortBy(?DataTableSorting $sortBy = null): self
    {
        if (null !== $sortBy) {
            $sortBy->validate();
            $this->sortBy[] = $sortBy;
        }

        return $this;
    }

    /**
     * @param DataTableSorting[] $sortBys
     *
     * @return static
     */
    public function addSortBys(?array $sortBys = []): self
    {
        if (null !== $sortBys) {
            foreach ($sortBys as $sortBy) {
                $this->addSortBy($sortBy);
            }
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function validate(): void
    {
        Assertion::notNull($this->data);
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize(): array
    {
        $properties = $this->getSetProperties();
        $properties['role'] = 'datatable';
        return $properties;
    }
}
