<?php
declare(strict_types=1);

namespace Triniti\Tests\AppleNews;

use Assert\AssertionFailedException;
use PHPUnit\Framework\TestCase;
use Triniti\AppleNews\DataTableSorting;

class DataTableSortingTest extends TestCase
{
    protected DataTableSorting $dataTableSorting;

    public function setUp(): void
    {
        $this->dataTableSorting = new DataTableSorting();
    }

    public function testCreateDataTableSorting(): void
    {
        $this->assertInstanceOf('Triniti\AppleNews\DataTableSorting', $this->dataTableSorting);
    }

    public function testGetSetDescriptor(): void
    {
        $this->assertNull($this->dataTableSorting->getDescriptor());
        $this->dataTableSorting->setDescriptor('test');
        $this->assertEquals('test', $this->dataTableSorting->getDescriptor());
    }

    public function testGetSetDirection(): void
    {
        $this->assertNull($this->dataTableSorting->getDirection());
        $this->dataTableSorting->setDirection('ascending');
        $this->assertEquals('ascending', $this->dataTableSorting->getDirection());
    }

    public function testSetInvalidDirection(): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->dataTableSorting->setDirection('test');
    }

    public function testValidate(): void
    {
        $this->expectException(AssertionFailedException::class);
        $dataTableSorting = new DataTableSorting();
        $dataTableSorting->validate();
    }

    public function testJsonSerialize(): void
    {
        $this->dataTableSorting
            ->setDescriptor('test')
            ->setDirection('ascending');
        $expectedJson = '{"descriptor":"test","direction":"ascending"}';
        $this->assertJsonStringEqualsJsonString($expectedJson, json_encode($this->dataTableSorting));
    }
}
