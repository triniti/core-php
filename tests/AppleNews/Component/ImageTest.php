<?php

namespace Triniti\Tests\AppleNews\Layout;

use Assert\AssertionFailedException;
use PHPUnit\Framework\TestCase;
use Triniti\AppleNews\Component\Image;
use Triniti\AppleNews\Link\ComponentLink;

class ImageTest extends TestCase
{
    protected Image $image;

    public function setUp(): void
    {
        $this->image = new Image();
    }

    public function testCreateImage(): void
    {
        $this->assertInstanceOf(
            'Triniti\AppleNews\Component\Image',
            $this->image
        );
    }

    public function testGetSetUrl(): void
    {
        $this->image->setURL('http://test.com');
        $this->assertEquals('http://test.com', $this->image->getURL());
    }

    public function testSetInvalidUrl(): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->image->setURL('test');
    }

    public function testGetSetAccessibilityCaption(): void
    {
        $this->image->setAccessibilityCaption('test');
        $this->assertEquals('test', $this->image->getAccessibilityCaption());

        $this->image->setAccessibilityCaption();
        $this->assertNull($this->image->getAccessibilityCaption());
    }

    public function testGetSetCaption(): void
    {
        $this->image->setCaption('test');
        $this->assertEquals('test', $this->image->getCaption());

        $this->image->setCaption();
        $this->assertNull($this->image->getCaption());

        $this->image->setCaption(null);
        $this->assertNull($this->image->getCaption());
    }

    public function testGetSetExplicitContent(): void
    {
        $this->image->setExplicitContent(true);
        $this->assertTrue($this->image->getExplicitContent());

        $this->image->setExplicitContent();
        $this->assertFalse($this->image->getExplicitContent());
    }

    public function testGetSetAddAddition(): void
    {
        $link = new ComponentLink();
        $link->setURL('http://test.com');

        $link2 = new ComponentLink();
        $link2->setURL('http://test2.com');
        $additions[] = $link;

        $this->image->setAdditions($additions);
        $this->assertEquals($additions, $this->image->getAdditions());

        $additions[] = $link2;
        $this->image->addAdditions($additions);
        $this->assertEquals([$link, $link, $link2], $this->image->getAdditions());

        $this->image->addAdditions();
        $this->assertEquals([$link, $link, $link2], $this->image->getAdditions());

        $this->image->addAdditions(null);
        $this->assertEquals([$link, $link, $link2], $this->image->getAdditions());

        $this->image->setAdditions();
        $this->assertEquals([], $this->image->getAdditions());

        $this->image->setAdditions(null);
        $this->assertEquals([], $this->image->getAdditions());
    }

    public function testValidate(): void
    {
        $this->expectException(AssertionFailedException::class);
        $image = new Image();
        $image->validate();
    }

    public function testJsonSerialize(): void
    {
        $link = new ComponentLink();
        $link->setURL('http://test.com');

        $expected = [
            'URL'                  => 'http://test.com',
            'caption'              => 'test',
            'accessibilityCaption' => 'caption',
            'explicitContent'      => true,
            'additions'            => [$link],
            'role'                 => 'image',
        ];

        $this->image
            ->setURL('http://test.com')
            ->setCaption('test')
            ->setAccessibilityCaption('caption')
            ->setAdditions([$link])
            ->setExplicitContent(true);

        $this->assertJsonStringEqualsJsonString(json_encode($expected), json_encode($this->image));
    }
}
