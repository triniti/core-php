<?php

namespace Triniti\Tests\AppleNews\Layout;

use Assert\AssertionFailedException;
use PHPUnit\Framework\TestCase;
use Triniti\AppleNews\Component\DataTable;
use Triniti\AppleNews\DataDescriptor;
use Triniti\AppleNews\DataTableSorting;
use Triniti\AppleNews\RecordStore;

class DataTableTest extends TestCase
{
    protected DataTable $dataTable;

    public function setup(): void
    {
        $this->dataTable = new DataTable();
    }

    public function testCreateDataTable(): void
    {
        $this->assertInstanceOf(
            'Triniti\AppleNews\Component\DataTable',
            $this->dataTable
        );
    }

    public function testGetSetData(): void
    {
        $recordStore = new RecordStore();
        $recordStore->setRecords(new \stdClass);

        $dataDescriptor = new DataDescriptor();
        $dataDescriptor->setDataType('string');
        $dataDescriptor->setKey('test');
        $dataDescriptor->setLabel('test');

        $recordStore->addDescriptor($dataDescriptor);

        $this->dataTable->setData($recordStore);
        $this->assertEquals($recordStore, $this->dataTable->getData());

        $this->dataTable->setData();
        $this->assertNull($this->dataTable->getData());
    }

    public function testGetSetDataOrientation(): void
    {
        $this->dataTable->setDataOrientation('vertical');
        $this->assertEquals('vertical', $this->dataTable->getDataOrientation());

        $this->dataTable->setDataOrientation();
        $this->assertEquals('horizontal', $this->dataTable->getDataOrientation());
    }

    public function testGetSetShowDescriptorLabels(): void
    {
        $this->dataTable->setShowDescriptorLabels(true);
        $this->assertTrue($this->dataTable->getShowDescriptorLabels());

        $this->dataTable->setShowDescriptorLabels();
        $this->assertTrue($this->dataTable->getShowDescriptorLabels());

        $this->dataTable->setShowDescriptorLabels(null);
        $this->assertTrue($this->dataTable->getShowDescriptorLabels());
    }

    public function testGetSetAddSortBy(): void
    {
        $dataTableSorting = new DataTableSorting();
        $dataTableSorting->setDescriptor('test');
        $dataTableSorting->setDirection('ascending');

        $dataTableSorting2 = new DataTableSorting();
        $dataTableSorting2->setDescriptor('test2');
        $dataTableSorting2->setDirection('ascending');

        $sortBys[] = $dataTableSorting;

        $this->dataTable->setSortBys($sortBys);
        $this->assertEquals($sortBys, $this->dataTable->getSortBys());

        $sortBys[] = $dataTableSorting2;
        $this->dataTable->addSortBys($sortBys);
        $this->assertEquals([$dataTableSorting, $dataTableSorting, $dataTableSorting2], $this->dataTable->getSortBys());

        $this->dataTable->addSortBys();
        $this->assertEquals([$dataTableSorting, $dataTableSorting, $dataTableSorting2], $this->dataTable->getSortBys());

        $this->dataTable->addSortBys(null);
        $this->assertEquals([$dataTableSorting, $dataTableSorting, $dataTableSorting2], $this->dataTable->getSortBys());

        $this->dataTable->setSortBys();
        $this->assertEquals([], $this->dataTable->getSortBys());

        $this->dataTable->setSortBys(null);
        $this->assertEquals([], $this->dataTable->getSortBys());
    }

    public function testValidate(): void
    {
        $this->expectException(AssertionFailedException::class);
        $a = new DataTable();
        $a->validate();
    }

    public function testJsonSerialize(): void
    {
        $recordStore = new RecordStore();
        $recordStore->setRecords(new \stdClass);

        $dataDescriptor = new DataDescriptor();
        $dataDescriptor->setDataType('string');
        $dataDescriptor->setKey('test');
        $dataDescriptor->setLabel('test');

        $recordStore->addDescriptor($dataDescriptor);

        $dataTableSorting = new DataTableSorting();
        $dataTableSorting->setDescriptor('test');
        $dataTableSorting->setDirection('ascending');

        $expected = [
            'data'                 => $recordStore,
            'dataOrientation'      => 'horizontal',
            'showDescriptorLabels' => true,
            'sortBy'               => [$dataTableSorting],
            'role'                 => 'datatable',
        ];

        $this->dataTable
            ->setData($recordStore)
            ->setDataOrientation('horizontal')
            ->setShowDescriptorLabels(true)
            ->setSortBys([$dataTableSorting]);

        $this->assertJsonStringEqualsJsonString(json_encode($expected), json_encode($this->dataTable));
    }
}
