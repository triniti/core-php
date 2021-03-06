<?php

namespace Triniti\Tests\AppleNews\Layout;

use Assert\AssertionFailedException;
use PHPUnit\Framework\TestCase;
use Triniti\AppleNews\Component\Gallery;
use Triniti\AppleNews\Component\GalleryItem;

class GalleryTest extends TestCase
{
    protected Gallery $gallery;

    public function setUp(): void
    {
        $this->gallery = new Gallery();
    }

    public function testCreateGallery(): void
    {
        $this->assertInstanceOf(
            'Triniti\AppleNews\Component\Gallery',
            $this->gallery
        );
    }

    public function testGetSetAddItem(): void
    {
        $galleryItem = new GalleryItem();
        $galleryItem->setURL('http://test.com');

        $galleryItem2 = new GalleryItem();
        $galleryItem2->setURL('http://test2.com');

        $items[] = $galleryItem;

        $this->gallery->setItems($items);
        $this->assertEquals($items, $this->gallery->getItems());

        $items[] = $galleryItem2;
        $this->gallery->addItems($items);
        $this->assertEquals([$galleryItem, $galleryItem, $galleryItem2], $this->gallery->getItems());

        $this->gallery->addItems();
        $this->assertEquals([$galleryItem, $galleryItem, $galleryItem2], $this->gallery->getItems());

        $this->gallery->addItems(null);
        $this->assertEquals([$galleryItem, $galleryItem, $galleryItem2], $this->gallery->getItems());

        $this->gallery->setItems();
        $this->assertEquals([], $this->gallery->getItems());

        $this->gallery->setItems(null);
        $this->assertEquals([], $this->gallery->getItems());
    }

    public function testValidate(): void
    {
        $this->expectException(AssertionFailedException::class);
        $gallery = new Gallery();
        $gallery->validate();
    }

    public function testJsonSerialize(): void
    {
        $galleryItem = new GalleryItem();
        $galleryItem->setURL('http://test.com');

        $expected = [
            'role'  => 'gallery',
            'items' => [$galleryItem],
        ];

        $this->gallery->addItem($galleryItem);

        $this->assertJsonStringEqualsJsonString(json_encode($expected), json_encode($this->gallery));
    }
}
