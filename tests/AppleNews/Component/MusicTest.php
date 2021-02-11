<?php

namespace Triniti\Tests\AppleNews\Layout;

use Assert\AssertionFailedException;
use PHPUnit\Framework\TestCase;
use Triniti\AppleNews\Component\Music;

class MusicTest extends TestCase
{
    protected Music $music;

    public function setUp(): void
    {
        $this->music = new Music();
    }

    public function testCreateMusic(): void
    {
        $this->assertInstanceOf(
            'Triniti\AppleNews\Component\Music',
            $this->music
        );
    }

    public function testGetSetUrl(): void
    {
        $this->music->setURL('http://test.com');
        $this->assertEquals('http://test.com', $this->music->getURL());
    }

    public function testSetInvalidUrl(): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->music->setURL('test');
    }

    public function testGetSetAccessibilityCaption(): void
    {
        $this->music->setAccessibilityCaption('test');
        $this->assertEquals('test', $this->music->getAccessibilityCaption());

        $this->music->setAccessibilityCaption();
        $this->assertNull($this->music->getAccessibilityCaption());
    }

    public function testGetSetCaption(): void
    {
        $this->music->setCaption('test');
        $this->assertEquals('test', $this->music->getCaption());

        $this->music->setCaption();
        $this->assertNull($this->music->getCaption());
    }

    public function testGetSetExplicitContent(): void
    {
        $this->music->setExplicitContent(true);
        $this->assertTrue($this->music->getExplicitContent());

        $this->music->setExplicitContent();
        $this->assertFalse($this->music->getExplicitContent());
    }

    public function testGetSetImageUrl(): void
    {
        $this->music->setImageURL('http://test.com');
        $this->assertEquals('http://test.com', $this->music->getImageURL());
    }

    public function testSetInvalidImageUrl(): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->music->setImageURL('test');
    }

    public function testValidate(): void
    {
        $this->expectException(AssertionFailedException::class);
        $music = new Music();
        $music->validate();
    }

    public function testJsonSerialize(): void
    {
        $expected = [
            'URL'                  => 'http://test.com',
            'caption'              => 'test',
            'accessibilityCaption' => 'caption',
            'explicitContent'      => true,
            'imageURL'             => 'http://test.com',
            'role'                 => 'music',
        ];

        $this->music
            ->setURL('http://test.com')
            ->setCaption('test')
            ->setAccessibilityCaption('caption')
            ->setExplicitContent(true)
            ->setImageURL('http://test.com');

        $this->assertJsonStringEqualsJsonString(json_encode($expected), json_encode($this->music));
    }
}
