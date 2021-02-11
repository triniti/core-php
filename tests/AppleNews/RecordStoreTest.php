<?php
declare(strict_types=1);

namespace Triniti\Tests\AppleNews;

use Assert\AssertionFailedException;
use PHPUnit\Framework\TestCase;
use Triniti\AppleNews\DataDescriptor;
use Triniti\AppleNews\RecordStore;

class RecordStoreTest extends TestCase
{
    protected RecordStore $recordStore;

    public function setUp(): void
    {
        $this->recordStore = new RecordStore();
    }

    public function testCreateRecordStore(): void
    {
        $this->assertInstanceOf('Triniti\AppleNews\RecordStore', $this->recordStore);
    }

    public function testGetSetAddDataDescriptor(): void
    {
        $data = new DataDescriptor();
        $data
            ->setKey('key')
            ->setLabel('test')
            ->setDataType('string');

        $data2 = new DataDescriptor();
        $data2
            ->setKey('key2')
            ->setLabel('test2')
            ->setDataType('string');

        $dataDescriptors[] = $data;

        $this->recordStore->setDescriptors($dataDescriptors);
        $this->assertEquals($dataDescriptors, $this->recordStore->getDescriptors());

        $dataDescriptors[] = $data2;
        $this->recordStore->addDescriptors($dataDescriptors);
        $this->assertEquals([$data, $data, $data2], $this->recordStore->getDescriptors());

        $this->recordStore->addDescriptors();
        $this->assertEquals([$data, $data, $data2], $this->recordStore->getDescriptors());

        $this->recordStore->setDescriptors();
        $this->assertEquals([], $this->recordStore->getDescriptors());
    }

    public function testGetSetRecords(): void
    {
        $record = new \stdClass();

        $this->recordStore->setRecords($record);
        $this->assertEquals($record, $this->recordStore->getRecords());
    }

    public function testValidate(): void
    {
        $this->expectException(AssertionFailedException::class);
        $recordStore = new RecordStore();
        $recordStore->validate();
    }

    public function testJsonSerialize(): void
    {
        $records = new \stdClass();

        $data = new DataDescriptor();
        $data
            ->setKey('key')
            ->setLabel('test')
            ->setDataType('string');

        $expected = [
            'records'     => $records,
            'descriptors' => [$data],
        ];

        $this->recordStore
            ->setRecords($records)
            ->setDescriptors([$data]);

        $this->assertJsonStringEqualsJsonString(json_encode($expected), json_encode($this->recordStore));
    }
}
