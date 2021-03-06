<?php

namespace Triniti\Tests\AppleNews\Layout;

use Assert\AssertionFailedException;
use PHPUnit\Framework\TestCase;
use Triniti\AppleNews\CaptionDescriptor;
use Triniti\AppleNews\Component\GalleryItem;

class GalleryItemTest extends TestCase
{
    protected GalleryItem $galleryItem;

    public function setUp(): void
    {
        $this->galleryItem = new GalleryItem();
    }

    public function testCreateGalleryItem(): void
    {
        $this->assertInstanceOf(
            'Triniti\AppleNews\Component\GalleryItem',
            $this->galleryItem
        );
    }

    public function testGetSetUrl(): void
    {
        $this->galleryItem->setURL('http://test.com');
        $this->assertEquals('http://test.com', $this->galleryItem->getURL());
    }

    public function testSetInvalidUrl(): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->galleryItem->setURL('test');
    }

    public function testGetSetAccessibilityCaption(): void
    {
        $this->galleryItem->setAccessibilityCaption('test');
        $this->assertEquals('test', $this->galleryItem->getAccessibilityCaption());

        $this->galleryItem->setAccessibilityCaption();
        $this->assertNull($this->galleryItem->getAccessibilityCaption());
    }

    public function testGetSetCaption(): void
    {
        $this->galleryItem->setCaption('test');
        $this->assertEquals('test', $this->galleryItem->getCaption());

        $captionDescriptor = new CaptionDescriptor();
        $captionDescriptor->setText('test');

        $this->galleryItem->setCaption($captionDescriptor);
        $this->assertEquals($captionDescriptor, $this->galleryItem->getCaption());

        $this->galleryItem->setCaption(null);
        $this->assertNull($this->galleryItem->getCaption());
    }

    public function testGetSetExplicitContent(): void
    {
        $this->galleryItem->setExplicitContent(true);
        $this->assertTrue($this->galleryItem->getExplicitContent());

        $this->galleryItem->setExplicitContent();
        $this->assertFalse($this->galleryItem->getExplicitContent());
    }

    public function testValidate(): void
    {
        $this->expectException(AssertionFailedException::class);
        $galleryItem = new GalleryItem();
        $galleryItem->validate();
    }

    public function testJsonSerialize(): void
    {
        $expected = [
            'URL'                  => 'http://test.com',
            'caption'              => 'test',
            'accessibilityCaption' => 'caption',
            'explicitContent'      => true,
        ];

        $this->galleryItem
            ->setURL('http://test.com')
            ->setCaption('test')
            ->setAccessibilityCaption('caption')
            ->setExplicitContent(true);

        $this->assertJsonStringEqualsJsonString(json_encode($expected), json_encode($this->galleryItem));
    }
}
