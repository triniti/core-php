<?php

namespace Triniti\Tests\AppleNews\Layout;

use Assert\AssertionFailedException;
use PHPUnit\Framework\TestCase;
use Triniti\AppleNews\Component\Video;

class VideoTest extends TestCase
{
    protected Video $video;

    public function setUp(): void
    {
        $this->video = new Video();
    }

    public function testCreateVideo(): void
    {
        $this->assertInstanceOf(
            'Triniti\AppleNews\Component\Video',
            $this->video
        );
    }

    public function testGetSetUrl(): void
    {
        $this->video->setURL('http://test.com');
        $this->assertEquals('http://test.com', $this->video->getURL());
    }

    public function testSetInvalidUrl(): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->video->setURL('test');
    }

    public function testGetSetAspectRatio(): void
    {
        $this->video->setAspectRatio(1.2);
        $this->assertEquals(1.2, $this->video->getAspectRatio());

        $this->video->setAspectRatio();
        $this->assertEquals(1.777, $this->video->getAspectRatio());
    }

    public function testGetSetAccessibilityCaption(): void
    {
        $this->video->setAccessibilityCaption('test');
        $this->assertEquals('test', $this->video->getAccessibilityCaption());

        $this->video->setAccessibilityCaption();
        $this->assertNull($this->video->getAccessibilityCaption());
    }

    public function testGetSetCaption(): void
    {
        $this->video->setCaption('test');
        $this->assertEquals('test', $this->video->getCaption());

        $this->video->setCaption();
        $this->assertNull($this->video->getCaption());
    }

    public function testGetSetExplicitContent(): void
    {
        $this->video->setExplicitContent(true);
        $this->assertTrue($this->video->getExplicitContent());

        $this->video->setExplicitContent();
        $this->assertFalse($this->video->getExplicitContent());
    }

    public function testGetSetStillUrl(): void
    {
        $this->video->setStillURL('http://test.com');
        $this->assertEquals('http://test.com', $this->video->getStillURL());
    }

    public function testSetInvalidStillUrl(): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->video->setStillURL('test');
    }

    public function testValidate(): void
    {
        $this->expectException(AssertionFailedException::class);
        $video = new Video();
        $video->validate();
    }

    public function testJsonSerialize(): void
    {
        $expected = [
            'URL'                  => 'http://test.com',
            'caption'              => 'test',
            'accessibilityCaption' => 'caption',
            'explicitContent'      => true,
            'stillURL'             => 'http://test.com',
            'role'                 => 'video',
        ];

        $this->video
            ->setURL('http://test.com')
            ->setCaption('test')
            ->setAccessibilityCaption('caption')
            ->setExplicitContent(true)
            ->setStillURL('http://test.com');

        $this->assertJsonStringEqualsJsonString(json_encode($expected), json_encode($this->video));
    }
}
