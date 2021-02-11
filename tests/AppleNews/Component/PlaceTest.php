<?php

namespace Triniti\Tests\AppleNews\Layout;

use Assert\AssertionFailedException;
use PHPUnit\Framework\TestCase;
use Triniti\AppleNews\Component\MapSpan;
use Triniti\AppleNews\Component\Place;

class PlaceTest extends TestCase
{
    protected Place $place;

    public function setUp(): void
    {
        $this->place = new Place();
    }

    public function testCreatePlace(): void
    {
        $this->assertInstanceOf(
            'Triniti\AppleNews\Component\Place',
            $this->place
        );
    }

    public function testGetSetLatitude(): void
    {
        $this->place->setLatitude(1.8);
        $this->assertEquals(1.8, $this->place->getLatitude());
    }

    public function testGetSetLongitude(): void
    {
        $this->place->setLongitude(1.8);
        $this->assertEquals(1.8, $this->place->getLongitude());
    }

    public function testGetSetAccessibilityCaption(): void
    {
        $this->place->setAccessibilityCaption('test');
        $this->assertEquals('test', $this->place->getAccessibilityCaption());

        $this->place->setAccessibilityCaption();
        $this->assertNull($this->place->getAccessibilityCaption());

        $this->place->setAccessibilityCaption(null);
        $this->assertNull($this->place->getAccessibilityCaption());
    }

    public function testGetSetCaption(): void
    {
        $this->place->setCaption('test');
        $this->assertEquals('test', $this->place->getCaption());

        $this->place->setCaption();
        $this->assertNull($this->place->getCaption());

        $this->place->setCaption(null);
        $this->assertNull($this->place->getCaption());
    }

    public function testGetSetMapType(): void
    {
        $this->place->setMapType('standard');
        $this->assertEquals('standard', $this->place->getMapType());

        $this->place->setMapType();
        $this->assertEquals('standard', $this->place->getMapType());

        $this->place->setMapType(null);
        $this->assertEquals('standard', $this->place->getMapType());
    }

    public function testSetInvalidMapType(): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->place->setMapType('test');
    }

    public function testGetSetSpan(): void
    {
        $span = new MapSpan();
        $span
            ->setLatitudeDelta(1.1)
            ->setLongitudeDelta(1.1);

        $this->place->setSpan($span);
        $this->assertEquals($span, $this->place->getSpan());

        $this->place->setSpan();
        $this->assertNull($this->place->getSpan());

        $this->place->setSpan(null);
        $this->assertNull($this->place->getSpan());
    }

    public function testValidate(): void
    {
        $this->expectException(AssertionFailedException::class);
        $place = new Place();
        $place->validate();
    }

    public function testJsonSerialize(): void
    {
        $span = new MapSpan();
        $span
            ->setLatitudeDelta(1.1)
            ->setLongitudeDelta(1.1);

        $expected = [
            'role'                 => 'place',
            'latitude'             => 1.1,
            'longitude'            => 1.1,
            'accessibilityCaption' => 'caption',
            'caption'              => 'test',
            'mapType'              => 'standard',
            'span'                 => $span,
        ];

        $this->place
            ->setLatitude(1.1)
            ->setLongitude(1.1)
            ->setAccessibilityCaption('caption')
            ->setCaption('test')
            ->setMapType()
            ->setSpan($span);

        $this->assertJsonStringEqualsJsonString(json_encode($expected), json_encode($this->place));
    }
}
