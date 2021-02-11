<?php

namespace Triniti\Tests\AppleNews\Layout;

use Assert\AssertionFailedException;
use PHPUnit\Framework\TestCase;
use Triniti\AppleNews\CaptionDescriptor;
use Triniti\AppleNews\Component\Figure;

class FigureTest extends TestCase
{
    protected Figure $figure;

    public function setUp(): void
    {
        $this->figure = new Figure();
    }

    public function testCreateFigure(): void
    {
        $this->assertInstanceOf(
            'Triniti\AppleNews\Component\Figure',
            $this->figure
        );
    }

    public function testGetSetUrl(): void
    {
        $this->figure->setURL('http://test.com');
        $this->assertEquals('http://test.com', $this->figure->getURL());
    }

    public function testSetInvalidUrl(): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->figure->setURL('test');
    }

    public function testGetSetAccessibilityCaption(): void
    {
        $this->figure->setAccessibilityCaption('test');
        $this->assertEquals('test', $this->figure->getAccessibilityCaption());

        $this->figure->setAccessibilityCaption();
        $this->assertNull($this->figure->getAccessibilityCaption());
    }

    public function testGetSetCaption(): void
    {
        $this->figure->setCaption('test');
        $this->assertEquals('test', $this->figure->getCaption());

        $captionDescriptor = new CaptionDescriptor();
        $captionDescriptor->setText('test');

        $this->figure->setCaption($captionDescriptor);
        $this->assertEquals($captionDescriptor, $this->figure->getCaption());

        $this->figure->setCaption(null);
        $this->assertNull($this->figure->getCaption());
    }

    public function testGetSetExplicitContent(): void
    {
        $this->figure->setExplicitContent(true);
        $this->assertTrue($this->figure->getExplicitContent());

        $this->figure->setExplicitContent();
        $this->assertFalse($this->figure->getExplicitContent());
    }

    public function testValidate(): void
    {
        $this->expectException(AssertionFailedException::class);
        $a = new Figure();
        $a->validate();
    }

    public function testJsonSerialize(): void
    {
        $expected = [
            'URL'                  => 'http://test.com',
            'caption'              => 'test',
            'accessibilityCaption' => 'caption',
            'explicitContent'      => true,
            'role'                 => 'figure',
        ];

        $this->figure
            ->setURL('http://test.com')
            ->setCaption('test')
            ->setAccessibilityCaption('caption')
            ->setExplicitContent(true);

        $this->assertJsonStringEqualsJsonString(json_encode($expected), json_encode($this->figure));
    }
}
