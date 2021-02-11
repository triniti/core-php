<?php

namespace Triniti\Tests\AppleNews\Layout;

use PHPUnit\Framework\TestCase;
use Triniti\AppleNews\Component\Intro;

class IntroTest extends TestCase
{
    protected Intro $intro;

    public function setUp(): void
    {
        $this->intro = new Intro();
    }

    public function testCreateIntro(): void
    {
        $this->assertInstanceOf(
            'Triniti\AppleNews\Component\Intro',
            $this->intro
        );
    }

    public function testJsonSerialize(): void
    {
        $expected = [
            'role' => 'intro',
        ];

        $this->assertJsonStringEqualsJsonString(json_encode($expected), json_encode($this->intro));
    }
}
