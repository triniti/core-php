<?php

namespace Triniti\Tests\AppleNews\Layout;

use Assert\AssertionFailedException;
use PHPUnit\Framework\TestCase;
use Triniti\AppleNews\CaptionDescriptor;
use Triniti\AppleNews\Component\Photo;

class PhotoTest extends TestCase
{
    protected Photo $photo;

    public function setUp(): void
    {
        $this->photo = new Photo();
    }

    public function testCreatePhoto(): void
    {
        $this->assertInstanceOf(
            'Triniti\AppleNews\Component\Photo',
            $this->photo
        );
    }

    public function testGetSetUrl(): void
    {
        $this->photo->setURL('http://test.com');
        $this->assertEquals('http://test.com', $this->photo->getURL());
    }

    public function testSetInvalidUrl(): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->photo->setURL('test');
    }

    public function testGetSetAccessibilityCaption(): void
    {
        $this->photo->setAccessibilityCaption('test');
        $this->assertEquals('test', $this->photo->getAccessibilityCaption());

        $this->photo->setAccessibilityCaption();
        $this->assertNull($this->photo->getAccessibilityCaption());
    }

    public function testGetSetCaption(): void
    {
        $this->photo->setCaption('test');
        $this->assertEquals('test', $this->photo->getCaption());

        $captionDescriptor = new CaptionDescriptor();
        $captionDescriptor->setText('test');

        $this->photo->setCaption($captionDescriptor);
        $this->assertEquals($captionDescriptor, $this->photo->getCaption());

        $this->photo->setCaption(null);
        $this->assertNull($this->photo->getCaption());
    }

    public function testGetSetExplicitContent(): void
    {
        $this->photo->setExplicitContent(true);
        $this->assertTrue($this->photo->getExplicitContent());

        $this->photo->setExplicitContent();
        $this->assertFalse($this->photo->getExplicitContent());
    }

    public function testValidate(): void
    {
        $this->expectException(AssertionFailedException::class);
        $photo = new Photo();
        $photo->validate();
    }

    public function testJsonSerialize(): void
    {
        $expected = [
            'URL'                  => 'http://test.com',
            'caption'              => 'test',
            'accessibilityCaption' => 'caption',
            'explicitContent'      => true,
            'role'                 => 'photo',
        ];

        $this->photo
            ->setURL('http://test.com')
            ->setCaption('test')
            ->setAccessibilityCaption('caption')
            ->setExplicitContent(true);

        $this->assertJsonStringEqualsJsonString(json_encode($expected), json_encode($this->photo));
    }
}
