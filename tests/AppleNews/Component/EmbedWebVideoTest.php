<?php

namespace Triniti\Tests\AppleNews\Layout;

use Assert\AssertionFailedException;
use PHPUnit\Framework\TestCase;
use Triniti\AppleNews\Component\EmbedWebVideo;

class EmbedWebVideoTest extends TestCase
{
    protected EmbedWebVideo $embedWebVideo;

    public function setUp(): void
    {
        $this->embedWebVideo = new EmbedWebVideo();
    }

    public function testCreateEmbedWebVideo(): void
    {
        $this->assertInstanceOf(
            'Triniti\AppleNews\Component\EmbedWebVideo',
            $this->embedWebVideo
        );
    }

    public function testGetSetRole(): void
    {
        $this->embedWebVideo->setRole('embedwebvideo');
        $this->assertEquals('embedwebvideo', $this->embedWebVideo->getRole());
    }

    public function testSetInvalidRole(): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->embedWebVideo->setRole('test');
    }

    public function testGetSetUrl(): void
    {
        $this->embedWebVideo->setURL('http://test.com');
        $this->assertEquals('http://test.com', $this->embedWebVideo->getURL());
    }

    public function testSetInvalidUrl(): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->embedWebVideo->setURL('test');
    }

    public function testGetSetAccessibilityCaption(): void
    {
        $this->embedWebVideo->setAccessibilityCaption('test');
        $this->assertEquals('test', $this->embedWebVideo->getAccessibilityCaption());

        $this->embedWebVideo->setAccessibilityCaption();
        $this->assertNull($this->embedWebVideo->getAccessibilityCaption());

        $this->embedWebVideo->setAccessibilityCaption(null);
        $this->assertNull($this->embedWebVideo->getAccessibilityCaption());
    }

    public function testGetSetAspectRatio(): void
    {
        $this->embedWebVideo->setAspectRatio(1.777);
        $this->assertEquals(1.777, $this->embedWebVideo->getAspectRatio());

        $this->embedWebVideo->setAspectRatio();
        $this->assertEquals(1.777, $this->embedWebVideo->getAspectRatio());
    }

    public function testGetSetCaption(): void
    {
        $this->embedWebVideo->setCaption('test');
        $this->assertEquals('test', $this->embedWebVideo->getCaption());

        $this->embedWebVideo->setCaption();
        $this->assertNull($this->embedWebVideo->getCaption());

        $this->embedWebVideo->setCaption(null);
        $this->assertNull($this->embedWebVideo->getCaption());
    }

    public function testGetSetExplicitContent(): void
    {
        $this->embedWebVideo->setExplicitContent(true);
        $this->assertTrue($this->embedWebVideo->getExplicitContent());

        $this->embedWebVideo->setExplicitContent();
        $this->assertFalse($this->embedWebVideo->getExplicitContent());
    }

    public function testValidate(): void
    {
        $this->expectException(AssertionFailedException::class);
        $a = new EmbedWebVideo();
        $a->validate();
    }

    public function testJsonSerialize(): void
    {
        $expected = [
            'URL'                  => 'http://test.com',
            'caption'              => 'test',
            'accessibilityCaption' => 'caption',
            'explicitContent'      => true,
            'aspectRatio'          => 1.777,
            'role'                 => 'embedwebvideo',
        ];

        $this->embedWebVideo
            ->setURL('http://test.com')
            ->setCaption('test')
            ->setAccessibilityCaption('caption')
            ->setExplicitContent(true)
            ->setAspectRatio(1.777)
            ->setRole('embedwebvideo');

        $this->assertJsonStringEqualsJsonString(json_encode($expected), json_encode($this->embedWebVideo));
    }
}
