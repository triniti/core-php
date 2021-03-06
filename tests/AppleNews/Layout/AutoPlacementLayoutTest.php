<?php
declare(strict_types=1);

namespace Triniti\Tests\AppleNews\Layout;

use PHPUnit\Framework\TestCase;
use Triniti\AppleNews\Layout\AutoPlacementLayout;
use Triniti\AppleNews\Layout\Margin;

class AutoPlacementLayoutTest extends TestCase
{
    protected AutoPlacementLayout $autoPlacementLayout;

    public function setUp(): void
    {
        $this->autoPlacementLayout = new AutoPlacementLayout();
    }

    public function testCreateAutoPlacementLayout(): void
    {
        $this->assertInstanceOf(
            'Triniti\AppleNews\Layout\AutoPlacementLayout',
            $this->autoPlacementLayout
        );

        $this->assertNull($this->autoPlacementLayout->getMargin());
    }

    public function testSetMargin(): void
    {
        $this->autoPlacementLayout->setMargin('test-margin-id');
        $this->assertSame('test-margin-id', $this->autoPlacementLayout->getMargin());

        $margin = new Margin();
        $margin->setTop(5)->setBottom(3);
        $this->autoPlacementLayout->setMargin($margin);

        $this->assertSame($margin, $this->autoPlacementLayout->getMargin());
    }

    public function testJsonSerialize(): void
    {
        $expectedDefault = [];
        $this->assertJsonStringEqualsJsonString(json_encode($expectedDefault), json_encode($this->autoPlacementLayout));

        $this->autoPlacementLayout->setMargin('test-id');
        $expected = [
            'margin' => 'test-id',
        ];
        $this->assertJsonStringEqualsJsonString(json_encode($expected), json_encode($this->autoPlacementLayout));

        $margin = new Margin();
        $margin->setTop(5)->setBottom(3);
        $this->autoPlacementLayout->setMargin($margin);
        $expected = [
            'margin' => ['top' => 5, 'bottom' => 3,],
        ];
        $this->assertJsonStringEqualsJsonString(json_encode($expected), json_encode($this->autoPlacementLayout));
    }
}
