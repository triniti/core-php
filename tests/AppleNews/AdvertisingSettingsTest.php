<?php

namespace Triniti\Tests\AppleNews;

use Assert\AssertionFailedException;
use PHPUnit\Framework\TestCase;
use Triniti\AppleNews\AdvertisingSettings;
use Triniti\AppleNews\Layout\AdvertisingLayout;
use Triniti\AppleNews\Layout\Margin;

class AdvertisingSettingsTest extends TestCase
{
    protected AdvertisingSettings $advertisingSettings;

    public function setUp(): void
    {
        $this->advertisingSettings = new AdvertisingSettings();
    }

    public function testCreateAdvertisingSettings(): void
    {
        $this->assertInstanceOf(
            'Triniti\AppleNews\AdvertisingSettings',
            $this->advertisingSettings
        );
    }

    public function testSetBannerType(): void
    {
        $this->assertNull($this->advertisingSettings->getBannerType());

        $this->advertisingSettings->setBannerType('standard');
        $this->assertSame('standard', $this->advertisingSettings->getBannerType());

        $this->advertisingSettings->setBannerType('large');
        $this->assertSame('large', $this->advertisingSettings->getBannerType());

        $this->advertisingSettings->setBannerType(null);
        $this->assertSame('any', $this->advertisingSettings->getBannerType());
    }

    public function testSetBannerTypeInvalid(): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->advertisingSettings->setBannerType('invalid');
    }

    public function testSetDistanceFromMedia(): void
    {
        $this->assertNull($this->advertisingSettings->getDistanceFromMedia());

        $this->advertisingSettings->setDistanceFromMedia(3);
        $this->assertSame(3, $this->advertisingSettings->getDistanceFromMedia());
    }

    public function testSetFrequency(): void
    {
        $this->assertNull($this->advertisingSettings->getFrequency());

        $this->advertisingSettings->setFrequency(5);
        $this->assertSame(5, $this->advertisingSettings->getFrequency());

        $this->advertisingSettings->setFrequency(null);
        $this->assertSame(0, $this->advertisingSettings->getFrequency());
    }

    public function testSetFrequencyInvalidLower(): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->advertisingSettings->setFrequency(-1);
    }

    public function testSetFrequencyInvalidUpper(): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->advertisingSettings->setFrequency(11);
    }

    public function testSetLayout(): void
    {
        $this->assertNull($this->advertisingSettings->getLayout());

        $layout = new AdvertisingLayout();
        $layout->setMargin(2);

        $this->advertisingSettings->setLayout($layout);
        $this->assertSame($layout, $this->advertisingSettings->getLayout());
    }

    public function testJsonSerialize(): void
    {
        $expected = [
            'frequency' => 10,
            'layout'    => [
                'margin' => [
                    'top'    => 15,
                    'bottom' => 20,
                ],
            ],
        ];

        $margin = new Margin();
        $margin->setBottom(20)->setTop(15);
        $layout = new AdvertisingLayout();
        $layout->setMargin($margin);

        $this->advertisingSettings->setLayout($layout)->setFrequency(10);

        $this->assertJsonStringEqualsJsonString(json_encode($expected), json_encode($this->advertisingSettings));
    }
}
