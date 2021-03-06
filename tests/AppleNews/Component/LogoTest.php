<?php

namespace Triniti\Tests\AppleNews\Layout;

use Assert\AssertionFailedException;
use PHPUnit\Framework\TestCase;
use Triniti\AppleNews\Component\Logo;
use Triniti\AppleNews\Link\ComponentLink;

class LogoTest extends TestCase
{
    protected Logo $logo;

    public function setUp(): void
    {
        $this->logo = new Logo();
    }

    public function testCreateLogo(): void
    {
        $this->assertInstanceOf(
            'Triniti\AppleNews\Component\Logo',
            $this->logo
        );
    }

    public function testGetSetUrl(): void
    {
        $this->logo->setURL('http://test.com');
        $this->assertEquals('http://test.com', $this->logo->getURL());
    }

    public function testSetInvalidUrl(): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->logo->setURL('test');
    }

    public function testGetSetAccessibilityCaption(): void
    {
        $this->logo->setAccessibilityCaption('test');
        $this->assertEquals('test', $this->logo->getAccessibilityCaption());

        $this->logo->setAccessibilityCaption();
        $this->assertNull($this->logo->getAccessibilityCaption());
    }

    public function testGetSetCaption(): void
    {
        $this->logo->setCaption('test');
        $this->assertEquals('test', $this->logo->getCaption());

        $this->logo->setCaption();
        $this->assertNull($this->logo->getCaption());

        $this->logo->setCaption(null);
        $this->assertNull($this->logo->getCaption());
    }

    public function testGetSetExplicitContent(): void
    {
        $this->logo->setExplicitContent(true);
        $this->assertTrue($this->logo->getExplicitContent());

        $this->logo->setExplicitContent();
        $this->assertFalse($this->logo->getExplicitContent());
    }

    public function testGetSetAddAddition(): void
    {
        $link = new ComponentLink();
        $link->setURL('http://test.com');

        $link2 = new ComponentLink();
        $link2->setURL('http://test2.com');
        $additions[] = $link;

        $this->logo->setAdditions($additions);
        $this->assertEquals($additions, $this->logo->getAdditions());

        $additions[] = $link2;
        $this->logo->addAdditions($additions);
        $this->assertEquals([$link, $link, $link2], $this->logo->getAdditions());

        $this->logo->addAdditions();
        $this->assertEquals([$link, $link, $link2], $this->logo->getAdditions());

        $this->logo->addAdditions(null);
        $this->assertEquals([$link, $link, $link2], $this->logo->getAdditions());

        $this->logo->setAdditions();
        $this->assertEquals([], $this->logo->getAdditions());

        $this->logo->setAdditions(null);
        $this->assertEquals([], $this->logo->getAdditions());
    }

    public function testValidate(): void
    {
        $this->expectException(AssertionFailedException::class);
        $logo = new Logo();
        $logo->validate();
    }

    public function testJsonSerialize(): void
    {
        $link = new ComponentLink();
        $link->setURL('http://test.com');

        $expected = [
            'URL'                  => 'http://test.com',
            'caption'              => 'test',
            'accessibilityCaption' => 'caption',
            'additions'            => [$link],
            'explicitContent'      => true,
            'role'                 => 'logo',
        ];

        $this->logo
            ->setURL('http://test.com')
            ->setCaption('test')
            ->setAccessibilityCaption('caption')
            ->setAdditions([$link])
            ->setExplicitContent(true);

        $this->assertJsonStringEqualsJsonString(json_encode($expected), json_encode($this->logo));
    }
}
