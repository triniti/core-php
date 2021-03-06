<?php

namespace Triniti\Tests\AppleNews\Layout;

use Assert\AssertionFailedException;
use PHPUnit\Framework\TestCase;
use Triniti\AppleNews\Component\GalleryItem;
use Triniti\AppleNews\Component\Mosaic;

class MosaicTest extends TestCase
{
    protected Mosaic $mosaic;

    public function setUp(): void
    {
        $this->mosaic = new Mosaic();
    }

    public function testCreateMosaic(): void
    {
        $this->assertInstanceOf(
            'Triniti\AppleNews\Component\Mosaic',
            $this->mosaic
        );
    }

    public function testGetSetAddItem(): void
    {
        $galleryItem = new GalleryItem();
        $galleryItem->setURL('http://test.com');

        $galleryItem2 = new GalleryItem();
        $galleryItem2->setURL('http://test2.com');

        $items[] = $galleryItem;

        $this->mosaic->setItems($items);
        $this->assertEquals($items, $this->mosaic->getItems());

        $items[] = $galleryItem2;
        $this->mosaic->addItems($items);
        $this->assertEquals([$galleryItem, $galleryItem, $galleryItem2], $this->mosaic->getItems());

        $this->mosaic->addItems();
        $this->assertEquals([$galleryItem, $galleryItem, $galleryItem2], $this->mosaic->getItems());

        $this->mosaic->addItems(null);
        $this->assertEquals([$galleryItem, $galleryItem, $galleryItem2], $this->mosaic->getItems());

        $this->mosaic->setItems();
        $this->assertEquals([], $this->mosaic->getItems());

        $this->mosaic->setItems(null);
        $this->assertEquals([], $this->mosaic->getItems());
    }

    public function testValidate(): void
    {
        $this->expectException(AssertionFailedException::class);
        $mosaic = new Mosaic();
        $mosaic->validate();
    }

    public function testJsonSerialize(): void
    {
        $galleryItem = new GalleryItem();
        $galleryItem->setURL('http://test.com');

        $expected = [
            'role'  => 'mosaic',
            'items' => [$galleryItem],
        ];

        $this->mosaic->addItem($galleryItem);

        $this->assertJsonStringEqualsJsonString(json_encode($expected), json_encode($this->mosaic));
    }
}
