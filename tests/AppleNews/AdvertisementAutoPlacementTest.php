<?php

namespace Triniti\Tests\AppleNews;

use Assert\AssertionFailedException;
use PHPUnit\Framework\TestCase;
use Triniti\AppleNews\AdvertisementAutoPlacement;
use Triniti\AppleNews\Condition;
use Triniti\AppleNews\Layout\AutoPlacementLayout;
use Triniti\AppleNews\ConditionalAutoPlacement;
use Triniti\AppleNews\Layout\Margin;

class AdvertisementAutoPlacementTest extends TestCase
{
    protected AdvertisementAutoPlacement $advertisementAutoPlacement;

    public function setUp(): void
    {
        $this->advertisementAutoPlacement = new AdvertisementAutoPlacement();
    }

    public function testCreateAdvertisementAutoPlacement(): void
    {
        $this->assertInstanceOf(
            'Triniti\AppleNews\AdvertisementAutoPlacement',
            $this->advertisementAutoPlacement
        );
    }

    public function testSetBannerType(): void
    {
        $this->assertNull($this->advertisementAutoPlacement->getBannerType());

        $this->advertisementAutoPlacement->setBannerType('standard');
        $this->assertSame('standard', $this->advertisementAutoPlacement->getBannerType());

        $this->advertisementAutoPlacement->setBannerType('large');
        $this->assertSame('large', $this->advertisementAutoPlacement->getBannerType());

        $this->advertisementAutoPlacement->setBannerType(null);
        $this->assertSame('any', $this->advertisementAutoPlacement->getBannerType());
    }

    public function testSetBannerTypeInvalid(): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->advertisementAutoPlacement->setBannerType('invalid');
    }

    public function testGetSetAddConditional(): void
    {
        $conditional1 = new ConditionalAutoPlacement();
        $condition1 = new Condition();
        $condition1->setMaxColumns(1);
        $conditional1->addCondition($condition1);


        $conditional2 = new ConditionalAutoPlacement();
        $condition2 = new Condition();
        $condition2->setPlatform('any');
        $conditional2->addCondition($condition2);

        $conditional = [$conditional1];

        $this->advertisementAutoPlacement->setConditional($conditional);
        $this->assertEquals($conditional, $this->advertisementAutoPlacement->getConditional());

        $conditional[] = $conditional2;
        $this->advertisementAutoPlacement->addConditionals($conditional);
        $this->assertEquals([$conditional1, $conditional1, $conditional2], $this->advertisementAutoPlacement->getConditional());

        $this->advertisementAutoPlacement->addConditionals();
        $this->assertEquals([$conditional1, $conditional1, $conditional2], $this->advertisementAutoPlacement->getConditional());

        $this->advertisementAutoPlacement->addConditionals(null);
        $this->assertEquals([$conditional1, $conditional1, $conditional2], $this->advertisementAutoPlacement->getConditional());

        $this->advertisementAutoPlacement->setConditional();
        $this->assertEquals([], $this->advertisementAutoPlacement->getConditional());

        $this->advertisementAutoPlacement->setConditional(null);
        $this->assertEquals([], $this->advertisementAutoPlacement->getConditional());
    }

    public function testGetSetEnabled(): void
    {
        $this->advertisementAutoPlacement->setEnabled(true);
        $this->assertTrue($this->advertisementAutoPlacement->getEnabled());
    }

    public function testSetDistanceFromMedia(): void
    {
        $this->assertNull($this->advertisementAutoPlacement->getDistanceFromMedia());

        $this->advertisementAutoPlacement->setDistanceFromMedia(3);
        $this->assertSame(3, $this->advertisementAutoPlacement->getDistanceFromMedia());
    }

    public function testSetFrequency(): void
    {
        $this->assertNull($this->advertisementAutoPlacement->getFrequency());

        $this->advertisementAutoPlacement->setFrequency(5);
        $this->assertSame(5, $this->advertisementAutoPlacement->getFrequency());

        $this->advertisementAutoPlacement->setFrequency(null);
        $this->assertSame(0, $this->advertisementAutoPlacement->getFrequency());
    }

    public function testSetFrequencyInvalidLower(): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->advertisementAutoPlacement->setFrequency(-1);
    }

    public function testSetFrequencyInvalidUpper(): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->advertisementAutoPlacement->setFrequency(11);
    }

    public function testSetLayout(): void
    {
        $this->assertNull($this->advertisementAutoPlacement->getLayout());

        $layout = new AutoPlacementLayout();
        $layout->setMargin(2);

        $this->advertisementAutoPlacement->setLayout($layout);
        $this->assertSame($layout, $this->advertisementAutoPlacement->getLayout());
    }

    public function testJsonSerialize(): void
    {
        $expected = [
            'conditional' => [['conditions' => [['maxColumns' => 1]], 'enabled' => false,]],
            'enabled'     => false,
            'frequency'   => 10,
            'layout'      => ['margin' => ['top' => 15, 'bottom' => 20,],
            ],
        ];

        $conditional1 = new ConditionalAutoPlacement();
        $condition1 = new Condition();
        $condition1->setMaxColumns(1);
        $conditional1->addCondition($condition1);
        $conditional = [$conditional1];

        $this->advertisementAutoPlacement->setConditional($conditional);

        $margin = new Margin();
        $margin->setBottom(20)->setTop(15);
        $layout = new AutoPlacementLayout();
        $layout->setMargin($margin);

        $this->advertisementAutoPlacement->setLayout($layout)->setFrequency(10);

        $this->assertJsonStringEqualsJsonString(json_encode($expected), json_encode($this->advertisementAutoPlacement));
    }
}
