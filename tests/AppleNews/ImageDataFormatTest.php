<?php

namespace Triniti\Tests\AppleNews\Layout;

use Assert\AssertionFailedException;
use PHPUnit\Framework\TestCase;
use Triniti\AppleNews\ImageDataFormat;
use Triniti\AppleNews\SupportedUnits;
use Triniti\Tests\AppleNews\AbstractPbjxTest;

class ImageDataFormatTest extends TestCase
{
    protected ImageDataFormat $imageDataFormat;

    public function setUp(): void
    {
        $this->imageDataFormat = new ImageDataFormat();
    }

    public function testCreateImageDataFormat(): void
    {
        $this->assertInstanceOf(
            'Triniti\AppleNews\ImageDataFormat',
            $this->imageDataFormat
        );
    }

    public function testGetSetMaximumWidth(): void
    {
        $this->imageDataFormat->setMaximumWidth(1);
        $this->assertEquals(1, $this->imageDataFormat->getMaximumWidth());

        $supportedUnits = new SupportedUnits('1pt');
        $this->imageDataFormat->setMaximumWidth($supportedUnits);
        $this->assertEquals($supportedUnits, $this->imageDataFormat->getMaximumWidth());

        $this->imageDataFormat->setMaximumWidth(null);
        $this->assertNull($this->imageDataFormat->getMaximumWidth());
    }

    public function testSetInvalidMaximumWidth(): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->imageDataFormat->setMaximumWidth('test');
    }

    public function testGetSetMaximumHeight(): void
    {
        $this->imageDataFormat->setMaximumHeight(1);
        $this->assertEquals(1, $this->imageDataFormat->getMaximumHeight());

        $supportedUnits = new SupportedUnits('1pt');
        $this->imageDataFormat->setMaximumHeight($supportedUnits);
        $this->assertEquals($supportedUnits, $this->imageDataFormat->getMaximumHeight());

        $this->imageDataFormat->setMaximumHeight(null);
        $this->assertNull($this->imageDataFormat->getMaximumHeight());
    }

    public function testSetInvalidMaximumHeight(): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->imageDataFormat->setMaximumWidth('test');
    }

    public function testGetSetMinimumWidth(): void
    {
        $this->imageDataFormat->setMinimumWidth(1);
        $this->assertEquals(1, $this->imageDataFormat->getMinimumWidth());

        $supportedUnits = new SupportedUnits('1pt');
        $this->imageDataFormat->setMinimumWidth($supportedUnits);
        $this->assertEquals($supportedUnits, $this->imageDataFormat->getMinimumWidth());

        $this->imageDataFormat->setMinimumWidth(null);
        $this->assertNull($this->imageDataFormat->getMinimumWidth());
    }

    public function testSetInvalidMinimumWidth(): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->imageDataFormat->setMinimumWidth('test');
    }

    public function testGetSetMinimumHeight(): void
    {
        $this->imageDataFormat->setMinimumHeight(1);
        $this->assertEquals(1, $this->imageDataFormat->getMinimumHeight());

        $supportedUnits = new SupportedUnits('1pt');
        $this->imageDataFormat->setMinimumHeight($supportedUnits);
        $this->assertEquals($supportedUnits, $this->imageDataFormat->getMinimumHeight());

        $this->imageDataFormat->setMinimumHeight(null);
        $this->assertNull($this->imageDataFormat->getMinimumHeight());
    }

    public function testSetInvalidMinimumHeight(): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->imageDataFormat->setMinimumHeight('test');
    }

    public function testJsonSerialize(): void
    {
        $expected = [
            'type'          => 'image',
            'maximumWidth'  => 1,
            'minimumWidth'  => 1,
            'maximumHeight' => 2,
            'minimumHeight' => 2,
        ];

        $this->imageDataFormat
            ->setMaximumWidth(1)
            ->setMinimumWidth(1)
            ->setMaximumHeight(2)
            ->setMinimumHeight(2);

        $this->assertJsonStringEqualsJsonString(json_encode($expected), json_encode($this->imageDataFormat));
    }
}
