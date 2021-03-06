<?php

namespace Triniti\Tests\AppleNews\Layout;

use Assert\AssertionFailedException;
use PHPUnit\Framework\TestCase;
use Triniti\AppleNews\Component\Instagram;

class InstagramTest extends TestCase
{
    protected Instagram $instagram;

    public function setup(): void
    {
        $this->instagram = new Instagram();
    }

    public function testCreateInstagram(): void
    {
        $this->assertInstanceOf(
            'Triniti\AppleNews\Component\Instagram',
            $this->instagram
        );
    }

    public function testGetSetUrl(): void
    {
        $this->instagram->setURL('http://test.com');
        $this->assertEquals('http://test.com', $this->instagram->getURL());
    }

    public function testSetInvalidUrl(): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->instagram->setURL('test');
    }

    public function testValidate(): void
    {
        $this->expectException(AssertionFailedException::class);
        $instagram = new Instagram();
        $instagram->validate();
    }

    public function testJsonSerialize(): void
    {
        $expected = [
            'role' => 'instagram',
            'URL'  => 'http://test.com',
        ];

        $this->instagram->setURL('http://test.com');

        $this->assertJsonStringEqualsJsonString(json_encode($expected), json_encode($this->instagram));
    }
}
