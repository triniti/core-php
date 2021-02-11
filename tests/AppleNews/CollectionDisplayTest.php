<?php

namespace Triniti\Tests\AppleNews\Layout;

use Assert\AssertionFailedException;
use PHPUnit\Framework\TestCase;
use Triniti\AppleNews\CollectionDisplay;
use Triniti\AppleNews\SupportedUnits;
use Triniti\Tests\AppleNews\AbstractPbjxTest;

class CollectionDisplayTest extends TestCase
{
    protected CollectionDisplay $collectionDisplay;

    public function setUp(): void
    {
        $this->collectionDisplay = new CollectionDisplay();
    }

    public function testCreateCollectionDisplay(): void
    {
        $this->assertInstanceOf(
            'Triniti\AppleNews\CollectionDisplay',
            $this->collectionDisplay
        );
    }

    public function testGetSetAlignment(): void
    {
        $this->collectionDisplay->setAlignment('center');
        $this->assertEquals('center', $this->collectionDisplay->getAlignment());

        $this->collectionDisplay->setAlignment();
        $this->assertEquals('left', $this->collectionDisplay->getAlignment());

        $this->collectionDisplay->setAlignment(null);
        $this->assertEquals('left', $this->collectionDisplay->getAlignment());
    }

    public function testSetInvalidAlignment(): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->collectionDisplay->setAlignment('test');
    }

    public function testGetSetDistribution(): void
    {
        $this->collectionDisplay->setDistribution('wide');
        $this->assertEquals('wide', $this->collectionDisplay->getDistribution());

        $this->collectionDisplay->setDistribution();
        $this->assertEquals('wide', $this->collectionDisplay->getDistribution());

        $this->collectionDisplay->setDistribution(null);
        $this->assertEquals('wide', $this->collectionDisplay->getDistribution());
    }

    public function testSetInvalidDistribution(): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->collectionDisplay->setDistribution('test');
    }

    public function testGetSetGutter(): void
    {
        $this->collectionDisplay->setGutter(1);
        $this->assertEquals(1, $this->collectionDisplay->getGutter());

        $supportedUnits = new SupportedUnits('1pt');
        $this->collectionDisplay->setGutter($supportedUnits);
        $this->assertEquals($supportedUnits, $this->collectionDisplay->getGutter());

        $this->collectionDisplay->setGutter(null);
        $this->assertNull($this->collectionDisplay->getGutter());
    }

    public function testSetInvalidGutter(): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->collectionDisplay->setGutter('test');
    }

    public function testGetSetMaximumWidth(): void
    {
        $this->collectionDisplay->setMaximumWidth(1);
        $this->assertEquals(1, $this->collectionDisplay->getMaximumWidth());

        $supportedUnits = new SupportedUnits('1pt');
        $this->collectionDisplay->setMaximumWidth($supportedUnits);
        $this->assertEquals($supportedUnits, $this->collectionDisplay->getMaximumWidth());

        $this->collectionDisplay->setMaximumWidth(null);
        $this->assertNull($this->collectionDisplay->getMaximumWidth());
    }

    public function testSetInvalidMaximumWidth(): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->collectionDisplay->setMaximumWidth('test');
    }

    public function testGetSetMinimumWidth(): void
    {
        $this->collectionDisplay->setMinimumWidth(1);
        $this->assertEquals(1, $this->collectionDisplay->getMinimumWidth());

        $supportedUnits = new SupportedUnits('1pt');
        $this->collectionDisplay->setMinimumWidth($supportedUnits);
        $this->assertEquals($supportedUnits, $this->collectionDisplay->getMinimumWidth());

        $this->collectionDisplay->setMinimumWidth(null);
        $this->assertNull($this->collectionDisplay->getMinimumWidth());
    }

    public function testSetInvalidMinimumWidth(): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->collectionDisplay->setMinimumWidth('test');
    }

    public function testGetSetRowSpacing(): void
    {
        $this->collectionDisplay->setRowSpacing(1);
        $this->assertEquals(1, $this->collectionDisplay->getRowSpacing());

        $supportedUnits = new SupportedUnits('1pt');
        $this->collectionDisplay->setRowSpacing($supportedUnits);
        $this->assertEquals($supportedUnits, $this->collectionDisplay->getRowSpacing());

        $this->collectionDisplay->setRowSpacing(null);
        $this->assertNull($this->collectionDisplay->getRowSpacing());
    }

    public function testSetInvalidRowSpacing(): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->collectionDisplay->setRowSpacing('test');
    }

    public function testGetSetVariableSizing(): void
    {
        $this->collectionDisplay->setVariableSizing(true);
        $this->assertTrue($this->collectionDisplay->getVariableSizing());

        $this->collectionDisplay->setVariableSizing();
        $this->assertFalse($this->collectionDisplay->getVariableSizing());
    }

    public function testGetSetWidows(): void
    {
        $this->collectionDisplay->setWidows('equalize');
        $this->assertEquals('equalize', $this->collectionDisplay->getWidows());

        $this->collectionDisplay->setWidows();
        $this->assertEquals('optimize', $this->collectionDisplay->getWidows());

        $this->collectionDisplay->setAlignment(null);
        $this->assertEquals('optimize', $this->collectionDisplay->getWidows());
    }

    public function testSetInvalidWidows(): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->collectionDisplay->setAlignment('test');
    }

    public function testJsonSerialize(): void
    {
        $expected = [
            'type'           => 'collection',
            'alignment'      => 'left',
            'distribution'   => 'wide',
            'gutter'         => 1,
            'maximumWidth'   => 1,
            'minimumWidth'   => 1,
            'rowSpacing'     => 1,
            'variableSizing' => true,
            'widows'         => 'optimize',
        ];

        $this->collectionDisplay
            ->setAlignment('left')
            ->setDistribution('wide')
            ->setGutter(1)
            ->setMaximumWidth(1)
            ->setMinimumWidth(1)
            ->setRowSpacing(1)
            ->setVariableSizing(true)
            ->setWidows('optimize');

        $this->assertJsonStringEqualsJsonString(json_encode($expected), json_encode($this->collectionDisplay));
    }
}
