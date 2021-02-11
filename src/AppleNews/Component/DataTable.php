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
    protected ?RecordStore $data = null;
    protected string $dataOrientation;
    protected bool $showDescriptorLabels;

    /** @var DataTableSorting[] */
    protected array $sortBy = [];

    public function getData(): ?RecordStore
    {
        return $this->data;
    }

    public function setData(?RecordStore $data = null): self
    {
        if (null !== $data) {
            $data->validate();
        }

        $this->data = $data;
        return $this;
    }

    public function getDataOrientation(): ?string
    {
        return $this->dataOrientation;
    }

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

    public function getShowDescriptorLabels(): ?bool
    {
        return $this->showDescriptorLabels;
    }

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

    public function validate(): void
    {
        Assertion::notNull($this->data);
    }

    public function jsonSerialize()
    {
        $properties = $this->getSetProperties();
        $properties['role'] = 'datatable';
        return $properties;
    }
}
