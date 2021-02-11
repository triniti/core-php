<?php

namespace Triniti\Tests\AppleNews\Layout;

use Assert\AssertionFailedException;
use PHPUnit\Framework\TestCase;
use Triniti\AppleNews\Component\Audio;

class AudioTest extends TestCase
{
    protected Audio $audio;

    public function setUp(): void
    {
        $this->audio = new Audio();
    }

    public function testCreateAudio(): void
    {
        $this->assertInstanceOf(
            'Triniti\AppleNews\Component\Audio',
            $this->audio
        );
    }

    public function testGetSetUrl(): void
    {
        $this->audio->setURL('http://test.com');
        $this->assertEquals('http://test.com', $this->audio->getURL());
    }

    public function testSetInvalidUrl(): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->audio->setURL('test');
    }

    public function testGetSetAccessibilityCaption(): void
    {
        $this->audio->setAccessibilityCaption('test');
        $this->assertEquals('test', $this->audio->getAccessibilityCaption());

        $this->audio->setAccessibilityCaption();
        $this->assertNull($this->audio->getAccessibilityCaption());
    }

    public function testGetSetCaption(): void
    {
        $this->audio->setCaption('test');
        $this->assertEquals('test', $this->audio->getCaption());

        $this->audio->setCaption();
        $this->assertNull($this->audio->getCaption());
    }

    public function testGetSetExplicitContent(): void
    {
        $this->audio->setExplicitContent(true);
        $this->assertTrue($this->audio->getExplicitContent());

        $this->audio->setExplicitContent();
        $this->assertFalse($this->audio->getExplicitContent());
    }

    public function testGetSetImageUrl(): void
    {
        $this->audio->setImageURL('http://test.com');
        $this->assertEquals('http://test.com', $this->audio->getImageURL());
    }

    public function testSetInvalidImageUrl(): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->audio->setImageURL('test');
    }

    public function testValidate(): void
    {
        $this->expectException(AssertionFailedException::class);
        $a = new Audio();
        $a->validate();
    }

    public function testJsonSerialize(): void
    {
        $expected = [
            'URL'                  => 'http://test.com',
            'caption'              => 'test',
            'accessibilityCaption' => 'caption',
            'explicitContent'      => true,
            'imageURL'             => 'http://test.com',
            'role'                 => 'audio',
        ];

        $this->audio
            ->setURL('http://test.com')
            ->setCaption('test')
            ->setAccessibilityCaption('caption')
            ->setExplicitContent(true)
            ->setImageURL('http://test.com');

        $this->assertJsonStringEqualsJsonString(json_encode($expected), json_encode($this->audio));
    }
}
