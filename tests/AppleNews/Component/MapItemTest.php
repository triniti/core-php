<?php

namespace Triniti\Tests\AppleNews\Layout;

use Assert\AssertionFailedException;
use PHPUnit\Framework\TestCase;
use Triniti\AppleNews\Component\MapItem;

class MapItemTest extends TestCase
{
    protected MapItem $mapItem;

    public function setUp(): void
    {
        $this->mapItem = new MapItem();
    }

    public function testCreateMapItem(): void
    {
        $this->assertInstanceOf(
            'Triniti\AppleNews\Component\MapItem',
            $this->mapItem
        );
    }

    public function testGetSetCaption(): void
    {
        $this->mapItem->setCaption('test');
        $this->assertEquals('test', $this->mapItem->getCaption());

        $this->mapItem->setCaption();
        $this->assertNull($this->mapItem->getCaption());

        $this->mapItem->setCaption(null);
        $this->assertNull($this->mapItem->getCaption());
    }

    public function testGetSetLatitude(): void
    {
        $this->mapItem->setLatitude(1.8);
        $this->assertEquals(1.8, $this->mapItem->getLatitude());
    }

    public function testGetSetLongitude(): void
    {
        $this->mapItem->setLongitude(1.8);
        $this->assertEquals(1.8, $this->mapItem->getLongitude());
    }

    public function testValidate(): void
    {
        $this->expectException(AssertionFailedException::class);
        $mapItem = new MapItem();
        $mapItem->validate();
    }

    public function testJsonSerialize(): void
    {
        $expected = [
            'latitude'  => 1.1,
            'longitude' => 1.1,
            'caption'   => 'test',
        ];

        $this->mapItem
            ->setLatitude(1.1)
            ->setLongitude(1.1)
            ->setCaption('test');

        $this->assertJsonStringEqualsJsonString(json_encode($expected), json_encode($this->mapItem));
    }
}
