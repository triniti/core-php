<?php

namespace Triniti\Tests\AppleNews\Layout;

use Assert\AssertionFailedException;
use PHPUnit\Framework\TestCase;
use Triniti\AppleNews\CaptionDescriptor;
use Triniti\AppleNews\Component\Portrait;

class PortraitTest extends TestCase
{
    protected Portrait $portrait;

    public function setUp(): void
    {
        $this->portrait = new Portrait();
    }

    public function testCreatePortrait(): void
    {
        $this->assertInstanceOf(
            'Triniti\AppleNews\Component\Portrait',
            $this->portrait
        );
    }

    public function testGetSetUrl(): void
    {
        $this->portrait->setURL('http://test.com');
        $this->assertEquals('http://test.com', $this->portrait->getURL());
    }

    public function testSetInvalidUrl(): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->portrait->setURL('test');
    }

    public function testGetSetAccessibilityCaption(): void
    {
        $this->portrait->setAccessibilityCaption('test');
        $this->assertEquals('test', $this->portrait->getAccessibilityCaption());

        $this->portrait->setAccessibilityCaption();
        $this->assertNull($this->portrait->getAccessibilityCaption());
    }

    public function testGetSetCaption(): void
    {
        $this->portrait->setCaption('test');
        $this->assertEquals('test', $this->portrait->getCaption());

        $captionDescriptor = new CaptionDescriptor();
        $captionDescriptor->setText('test');

        $this->portrait->setCaption($captionDescriptor);
        $this->assertEquals($captionDescriptor, $this->portrait->getCaption());

        $this->portrait->setCaption(null);
        $this->assertNull($this->portrait->getCaption());
    }

    public function testGetSetExplicitContent(): void
    {
        $this->portrait->setExplicitContent(true);
        $this->assertTrue($this->portrait->getExplicitContent());

        $this->portrait->setExplicitContent();
        $this->assertFalse($this->portrait->getExplicitContent());
    }


    public function testValidate(): void
    {
        $this->expectException(AssertionFailedException::class);
        $portrait = new Portrait();
        $portrait->validate();
    }

    public function testJsonSerialize(): void
    {
        $expected = [
            'URL'                  => 'http://test.com',
            'caption'              => 'test',
            'accessibilityCaption' => 'caption',
            'explicitContent'      => true,
            'role'                 => 'portrait',
        ];

        $this->portrait
            ->setURL('http://test.com')
            ->setCaption('test')
            ->setAccessibilityCaption('caption')
            ->setExplicitContent(true);

        $this->assertJsonStringEqualsJsonString(json_encode($expected), json_encode($this->portrait));
    }
}
