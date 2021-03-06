<?php

namespace Triniti\Tests\AppleNews\Layout;

use Assert\AssertionFailedException;
use PHPUnit\Framework\TestCase;
use Triniti\AppleNews\Component\MapSpan;

class MapSpanTest extends TestCase
{
    protected MapSpan $mapSpan;

    public function setUp(): void
    {
        $this->mapSpan = new MapSpan();
    }

    public function testCreateMapSpan(): void
    {
        $this->assertInstanceOf(
            'Triniti\AppleNews\Component\MapSpan',
            $this->mapSpan
        );
    }

    public function testGetSetLatitudeDelta(): void
    {
        $this->mapSpan->setLatitudeDelta(1.8);
        $this->assertEquals(1.8, $this->mapSpan->getLatitudeDelta());
    }

    public function testGetSetLongitudeDelta(): void
    {
        $this->mapSpan->setLongitudeDelta(1.8);
        $this->assertEquals(1.8, $this->mapSpan->getLongitudeDelta());
    }

    public function testValidate(): void
    {
        $this->expectException(AssertionFailedException::class);
        $mapSpan = new MapSpan();
        $mapSpan->validate();
    }

    public function testJsonSerialize(): void
    {
        $expected = [
            'latitudeDelta'  => 1.1,
            'longitudeDelta' => 1.1,
        ];

        $this->mapSpan
            ->setLatitudeDelta(1.1)
            ->setLongitudeDelta(1.1);

        $this->assertJsonStringEqualsJsonString(json_encode($expected), json_encode($this->mapSpan));
    }
}
