<?php

namespace Triniti\Tests\AppleNews\Layout;

use Assert\AssertionFailedException;
use PHPUnit\Framework\TestCase;
use Triniti\AppleNews\Component\FacebookPost;

class FacebookPostTest extends TestCase
{
    protected FacebookPost $facebookPost;

    public function setup(): void
    {
        $this->facebookPost = new FacebookPost();
    }

    public function testCreateFacebookPost(): void
    {
        $this->assertInstanceOf(
            'Triniti\AppleNews\Component\FacebookPost',
            $this->facebookPost
        );
    }

    public function testGetSetUrl(): void
    {
        $this->facebookPost->setURL('http://test.com');
        $this->assertEquals('http://test.com', $this->facebookPost->getURL());
    }

    public function testSetInvalidUrl(): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->facebookPost->setURL('test');
    }

    public function testValidate(): void
    {
        $this->expectException(AssertionFailedException::class);
        $a = new FacebookPost();
        $a->validate();
    }

    public function testJsonSerialize(): void
    {
        $expected = [
            'role' => 'facebook_post',
            'URL'  => 'http://test.com',
        ];

        $this->facebookPost->setURL('http://test.com');

        $this->assertJsonStringEqualsJsonString(json_encode($expected), json_encode($this->facebookPost));
    }
}
