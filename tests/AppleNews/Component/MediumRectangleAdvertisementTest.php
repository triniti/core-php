<?php

namespace Triniti\Tests\AppleNews\Layout;

use PHPUnit\Framework\TestCase;
use Triniti\AppleNews\Component\MediumRectangleAdvertisement;

class MediumRectangleAdvertisementTest extends TestCase
{
    protected MediumRectangleAdvertisement $mediumRectangleAdvertisement;

    public function setUp(): void
    {
        $this->mediumRectangleAdvertisement = new MediumRectangleAdvertisement();
    }

    public function testCreateMediumRectangleAdvertisement(): void
    {
        $this->assertInstanceOf(
            'Triniti\AppleNews\Component\MediumRectangleAdvertisement',
            $this->mediumRectangleAdvertisement
        );
    }

    public function testJsonSerialize(): void
    {
        $expected = [
            'role' => 'medium_rectangle_advertisement',
        ];

        $this->assertJsonStringEqualsJsonString(json_encode($expected), json_encode($this->mediumRectangleAdvertisement));
    }
}
