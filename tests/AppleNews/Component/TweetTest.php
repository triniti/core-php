<?php

namespace Triniti\Tests\AppleNews\Layout;

use Assert\AssertionFailedException;
use PHPUnit\Framework\TestCase;
use Triniti\AppleNews\Component\Tweet;

class TweetTest extends TestCase
{
    protected Tweet $tweet;

    public function setup(): void
    {
        $this->tweet = new Tweet();
    }

    public function testCreateTweet(): void
    {
        $this->assertInstanceOf(
            'Triniti\AppleNews\Component\Tweet',
            $this->tweet
        );
    }

    public function testGetSetUrl(): void
    {
        $this->tweet->setURL('http://test.com');
        $this->assertEquals('http://test.com', $this->tweet->getURL());
    }

    public function testSetInvalidUrl(): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->tweet->setURL('test');
    }

    public function testValidate(): void
    {
        $this->expectException(AssertionFailedException::class);
        $tweet = new Tweet();
        $tweet->validate();
    }

    public function testJsonSerialize(): void
    {
        $expected = [
            'role' => 'tweet',
            'URL'  => 'http://test.com',
        ];

        $this->tweet->setURL('http://test.com');

        $this->assertJsonStringEqualsJsonString(json_encode($expected), json_encode($this->tweet));
    }
}
