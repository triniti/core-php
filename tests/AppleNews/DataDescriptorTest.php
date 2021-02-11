<?php
declare(strict_types=1);

namespace Triniti\Tests\AppleNews;

use Assert\AssertionFailedException;
use PHPUnit\Framework\TestCase;
use Triniti\AppleNews\DataDescriptor;
use Triniti\AppleNews\FloatDataFormat;
use Triniti\AppleNews\Style\FormattedText;

class DataDescriptorTest extends TestCase
{
    protected DataDescriptor $dataDescriptor;

    public function setUp(): void
    {
        $this->dataDescriptor = new DataDescriptor();
    }

    public function testCreateDataDescriptor(): void
    {
        $this->assertInstanceOf('Triniti\AppleNews\DataDescriptor', $this->dataDescriptor);
    }

    public function testSetFormat(): void
    {
        $this->assertNull($this->dataDescriptor->getFormat());

        $format = new FloatDataFormat();
        $this->dataDescriptor->setFormat($format);
    }

    public function testSetDataType(): void
    {
        $this->assertNull($this->dataDescriptor->getDataType());

        $validTypes = [
            'string',
            'text',
            'image',
            'number',
            'integer',
            'float',
        ];

        foreach ($validTypes as $type) {
            $this->dataDescriptor->setDataType($type);
            $this->assertSame($type, $this->dataDescriptor->getDataType());
        }
    }

    public function testSetDateTypeInvalid(): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->dataDescriptor->setDataType('invalid');
    }

    public function testSetIdentifier(): void
    {
        $this->assertNull($this->dataDescriptor->getIdentifier());

        $this->dataDescriptor->setIdentifier('data-1');
        $this->assertSame('data-1', $this->dataDescriptor->getIdentifier());
    }

    public function testSetKey(): void
    {
        $this->assertNull($this->dataDescriptor->getKey());

        $this->dataDescriptor->setKey('key');
        $this->assertSame('key', $this->dataDescriptor->getKey());
    }

    public function testSetLabel(): void
    {
        $this->assertNull($this->dataDescriptor->getLabel());

        $this->dataDescriptor->setLabel('label');
        $this->assertSame('label', $this->dataDescriptor->getLabel());

        $formattedLabel = new FormattedText();
        $formattedLabel->setTextStyle('good-style')->setText('formated-label');
        $this->dataDescriptor->setLabel($formattedLabel);
        $this->assertSame($formattedLabel, $this->dataDescriptor->getLabel());
    }

    public function testValidate(): void
    {
        $this->dataDescriptor->setDataType('number')->setLabel('label')->setKey('key');
        try {
            $this->dataDescriptor->validate();
        } catch (\Exception $e) {
            $this->assertTrue(false);
        }

        $this->assertTrue(true, 'no exception should be thrown');
    }

    public function testValidationInvalid(): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->dataDescriptor->setDataType('number')->setLabel('label');
        $this->dataDescriptor->validate();
    }

    public function testJsonSerialize(): void
    {
        $excepted = [
            'identifier' => 'id-name',
            'key'        => 'name',
            'label'      => [
                'type' => 'formatted_text',
                'text' => 'Name',
            ],
            'dataType'   => 'string',
        ];

        $label = new FormattedText();
        $label->setText('Name');
        $this->dataDescriptor
            ->setIdentifier('id-name')
            ->setDataType('string')
            ->setLabel($label)
            ->setKey('name');

        $this->assertJsonStringEqualsJsonString(json_encode($excepted), json_encode($this->dataDescriptor));
    }
}
