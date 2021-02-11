<?php

namespace Triniti\Tests\AppleNews;

use PHPUnit\Framework\TestCase;
use Triniti\AppleNews\AdvertisementAutoPlacement;
use Triniti\AppleNews\AutoPlacement;

class AutoPlacementTest extends TestCase
{
    protected AutoPlacement $autoPlacement;

    public function setUp(): void
    {
        $this->autoPlacement = new AutoPlacement();
    }

    public function testCreateAutoPlacement(): void
    {
        $this->assertInstanceOf(
            'Triniti\AppleNews\AutoPlacement',
            $this->autoPlacement
        );
    }

    public function testSetGetAdvertisement(): void
    {
        $this->assertNull($this->autoPlacement->getAdvertisement());
        $advertisement = new AdvertisementAutoPlacement();
        $this->autoPlacement->setAdvertisement($advertisement);

        $this->assertSame($advertisement, $this->autoPlacement->getAdvertisement());

    }

    public function testJsonSerialize(): void
    {
        $advertisement = new AdvertisementAutoPlacement();
        $advertisement->setBannerType('any');
        $this->autoPlacement->setAdvertisement($advertisement);

        $expected = [
            'advertisement' => $advertisement,
        ];

        $this->assertJsonStringEqualsJsonString(json_encode($expected), json_encode($this->autoPlacement));
    }
}
