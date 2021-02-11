<?php
declare(strict_types=1);

namespace Triniti\Tests\AppleNews\Layout;

use PHPUnit\Framework\TestCase;
use Triniti\AppleNews\Layout\AdvertisingLayout;
use Triniti\AppleNews\Layout\Margin;

class AdvertisingLayoutTest extends TestCase
{
    protected AdvertisingLayout $advertisingLayout;

    public function setUp(): void
    {
        $this->advertisingLayout = new AdvertisingLayout();
    }

    public function testCreateAppearAnimation(): void
    {
        $this->assertInstanceOf(
            'Triniti\AppleNews\Layout\AdvertisingLayout',
            $this->advertisingLayout
        );

        $this->assertNull($this->advertisingLayout->getMargin());
    }

    public function testSetMargin(): void
    {
        $this->advertisingLayout->setMargin('test-margin-id');
        $this->assertSame('test-margin-id', $this->advertisingLayout->getMargin());

        $margin = new Margin();
        $margin->setTop(5)->setBottom(3);
        $this->advertisingLayout->setMargin($margin);

        $this->assertSame($margin, $this->advertisingLayout->getMargin());
    }

    public function testJsonSerialize(): void
    {
        $expectedDefault = [];
        $this->assertJsonStringEqualsJsonString(json_encode($expectedDefault), json_encode($this->advertisingLayout));

        $this->advertisingLayout->setMargin('test-id');
        $expected = [
            'margin' => 'test-id',
        ];
        $this->assertJsonStringEqualsJsonString(json_encode($expected), json_encode($this->advertisingLayout));

        $margin = new Margin();
        $margin->setTop(5)->setBottom(3);
        $this->advertisingLayout->setMargin($margin);
        $expected = [
            'margin' => [
                'top'    => 5,
                'bottom' => 3,
            ],
        ];
        $this->assertJsonStringEqualsJsonString(json_encode($expected), json_encode($this->advertisingLayout));
    }
}


