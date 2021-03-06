<?php

namespace Triniti\Tests\AppleNews\Layout;

use Assert\AssertionFailedException;
use PHPUnit\Framework\TestCase;
use Triniti\AppleNews\Component\BannerAdvertisement;

class BannerAdvertisementTest extends TestCase
{
    protected BannerAdvertisement $bannerAdvertisement;

    public function setUp(): void
    {
        $this->bannerAdvertisement = new BannerAdvertisement();
    }

    public function testCreateBannerAdvertisement(): void
    {
        $this->assertInstanceOf(
            'Triniti\AppleNews\Component\BannerAdvertisement',
            $this->bannerAdvertisement
        );
    }

    public function testGetSetBannerType(): void
    {
        $this->bannerAdvertisement->setBannerType('any');
        $this->assertEquals('any', $this->bannerAdvertisement->getBannerType());

        $this->bannerAdvertisement->setBannerType();
        $this->assertEquals('any', $this->bannerAdvertisement->getBannerType());
    }

    public function testSetInvalidFormat(): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->bannerAdvertisement->setBannerType('test');
    }

    public function testJsonSerialize(): void
    {
        $expected = [
            'role'       => 'banner_advertisement',
            'bannerType' => 'any',
        ];

        $this->bannerAdvertisement->setBannerType('any');

        $this->assertJsonStringEqualsJsonString(json_encode($expected), json_encode($this->bannerAdvertisement));
    }
}
