<?php

namespace Triniti\Tests\AppleNews\Layout;

use PHPUnit\Framework\TestCase;
use Triniti\AppleNews\Component\Photographer;

class PhotographerTest extends TestCase
{
    protected Photographer $photographer;

    public function setup(): void
    {
        $this->photographer = new Photographer();
    }

    public function testCreatePhotographer(): void
    {
        $this->assertInstanceOf(
            'Triniti\AppleNews\Component\Photographer',
            $this->photographer
        );
    }

    public function testJsonSerialize(): void
    {
        $expected = [
            'role' => 'photographer',
        ];

        $this->assertJsonStringEqualsJsonString(json_encode($expected), json_encode($this->photographer));
    }
}
