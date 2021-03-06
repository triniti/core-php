<?php

namespace Triniti\Tests\AppleNews\Layout;

use PHPUnit\Framework\TestCase;
use Triniti\AppleNews\Component\Quote;

class QuoteTest extends TestCase
{
    protected Quote $quote;

    public function setUp(): void
    {
        $this->quote = new Quote();
    }

    public function testCreateQuote(): void
    {
        $this->assertInstanceOf(
            'Triniti\AppleNews\Component\Quote',
            $this->quote
        );
    }

    public function testJsonSerialize(): void
    {
        $expected = [
            'role' => 'quote',
        ];

        $this->assertJsonStringEqualsJsonString(json_encode($expected), json_encode($this->quote));
    }
}
