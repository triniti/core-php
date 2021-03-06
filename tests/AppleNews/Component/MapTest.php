<?php

namespace Triniti\Tests\AppleNews\Layout;

use Assert\AssertionFailedException;
use PHPUnit\Framework\TestCase;
use Triniti\AppleNews\Component\Map;
use Triniti\AppleNews\Component\MapItem;
use Triniti\AppleNews\Component\MapSpan;

class MapTest extends TestCase
{
    protected Map $map;

    public function setUp(): void
    {
        $this->map = new Map();
    }

    public function testCreateMap(): void
    {
        $this->assertInstanceOf(
            'Triniti\AppleNews\Component\Map',
            $this->map
        );
    }

    public function testGetSetLatitude(): void
    {
        $this->map->setLatitude(1.8);
        $this->assertEquals(1.8, $this->map->getLatitude());
    }

    public function testGetSetLongitude(): void
    {
        $this->map->setLongitude(1.8);
        $this->assertEquals(1.8, $this->map->getLongitude());
    }

    public function testGetSetAccessibilityCaption(): void
    {
        $this->map->setAccessibilityCaption('test');
        $this->assertEquals('test', $this->map->getAccessibilityCaption());

        $this->map->setAccessibilityCaption();
        $this->assertNull($this->map->getAccessibilityCaption());

        $this->map->setAccessibilityCaption(null);
        $this->assertNull($this->map->getAccessibilityCaption());
    }

    public function testGetSetCaption(): void
    {
        $this->map->setCaption('test');
        $this->assertEquals('test', $this->map->getCaption());

        $this->map->setCaption();
        $this->assertNull($this->map->getCaption());

        $this->map->setCaption(null);
        $this->assertNull($this->map->getCaption());
    }

    public function testGetSetAddItem(): void
    {
        $mapItem = new MapItem();
        $mapItem
            ->setLongitude(1.8)
            ->setLatitude(1.8);

        $mapItem2 = new MapItem();
        $mapItem2
            ->setLongitude(1.9)
            ->setLatitude(1.9);

        $items[] = $mapItem;

        $this->map->setItems($items);
        $this->assertEquals($items, $this->map->getItems());

        $items[] = $mapItem2;
        $this->map->addItems($items);
        $this->assertEquals([$mapItem, $mapItem, $mapItem2], $this->map->getItems());

        $this->map->addItems();
        $this->assertEquals([$mapItem, $mapItem, $mapItem2], $this->map->getItems());

        $this->map->addItems(null);
        $this->assertEquals([$mapItem, $mapItem, $mapItem2], $this->map->getItems());

        $this->map->setItems();
        $this->assertEquals([], $this->map->getItems());

        $this->map->setItems(null);
        $this->assertEquals([], $this->map->getItems());
    }

    public function testGetSetMapType(): void
    {
        $this->map->setMapType('standard');
        $this->assertEquals('standard', $this->map->getMapType());

        $this->map->setMapType();
        $this->assertEquals('standard', $this->map->getMapType());

        $this->map->setMapType(null);
        $this->assertEquals('standard', $this->map->getMapType());
    }

    public function testSetInvalidMapType(): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->map->setMapType('test');
    }

    public function testGetSetSpan(): void
    {
        $span = new MapSpan();
        $span
            ->setLatitudeDelta(1.1)
            ->setLongitudeDelta(1.1);

        $this->map->setSpan($span);
        $this->assertEquals($span, $this->map->getSpan());

        $this->map->setSpan();
        $this->assertNull($this->map->getSpan());

        $this->map->setSpan(null);
        $this->assertNull($this->map->getSpan());
    }

    public function testValidate(): void
    {
        $this->expectException(AssertionFailedException::class);
        $map = new Map();
        $map->validate();
    }

    public function testJsonSerialize(): void
    {
        $span = new MapSpan();
        $span
            ->setLatitudeDelta(1.1)
            ->setLongitudeDelta(1.1);

        $mapItem = new MapItem();
        $mapItem
            ->setLongitude(1.8)
            ->setLatitude(1.8);

        $expected = [
            'role'                 => 'map',
            'latitude'             => 1.1,
            'longitude'            => 1.1,
            'accessibilityCaption' => 'caption',
            'caption'              => 'test',
            'items'                => [$mapItem],
            'mapType'              => 'standard',
            'span'                 => $span,
        ];

        $this->map
            ->setLatitude(1.1)
            ->setLongitude(1.1)
            ->setAccessibilityCaption('caption')
            ->setCaption('test')
            ->addItem($mapItem)
            ->setMapType()
            ->setSpan($span);

        $this->assertJsonStringEqualsJsonString(json_encode($expected), json_encode($this->map));
    }
}
