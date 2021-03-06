<?php

namespace Triniti\Tests\AppleNews\Layout;

use PHPUnit\Framework\TestCase;
use Triniti\AppleNews\Component\PullQuote;

class PullQuoteTest extends TestCase
{
    protected PullQuote $pullQuote;

    public function setUp(): void
    {
        $this->pullQuote = new PullQuote();
    }

    public function testCreatePullQuote(): void
    {
        $this->assertInstanceOf(
            'Triniti\AppleNews\Component\PullQuote',
            $this->pullQuote
        );
    }

    public function testJsonSerialize(): void
    {
        $expected = [
            'role' => 'pullquote',
        ];

        $this->assertJsonStringEqualsJsonString(json_encode($expected), json_encode($this->pullQuote));
    }
}
