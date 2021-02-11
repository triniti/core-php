<?php

namespace Triniti\Tests\AppleNews\Layout;

use PHPUnit\Framework\TestCase;
use Triniti\AppleNews\Component\Illustrator;

class IllustratorTest extends TestCase
{
    protected Illustrator $illustrator;

    public function setUp(): void
    {
        $this->illustrator = new Illustrator();
    }

    public function testCreateIllustrator(): void
    {
        $this->assertInstanceOf(
            'Triniti\AppleNews\Component\Illustrator',
            $this->illustrator
        );
    }

    public function testJsonSerialize(): void
    {
        $expected = [
            'role' => 'illustrator',
        ];

        $this->assertJsonStringEqualsJsonString(json_encode($expected), json_encode($this->illustrator));
    }
}
