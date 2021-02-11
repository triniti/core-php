<?php

namespace Triniti\Tests\AppleNews\Layout;

use Assert\AssertionFailedException;
use PHPUnit\Framework\TestCase;
use Triniti\AppleNews\Component\Heading;

class HeadingTest extends TestCase
{
    protected Heading $heading;

    public function setUp(): void
    {
        $this->heading = new Heading();
    }

    public function testCreateHeading(): void
    {
        $this->assertInstanceOf(
            'Triniti\AppleNews\Component\Heading',
            $this->heading
        );
    }

    public function testGetSetRole(): void
    {
        $this->heading->setRole('heading');
        $this->assertEquals('heading', $this->heading->getRole());
    }

    public function testSetInvalidRole(): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->heading->setRole('test');
    }

    public function testValidate(): void
    {
        $this->expectException(AssertionFailedException::class);
        $heading = new Heading();
        $heading->validate();
    }

    public function testJsonSerialize(): void
    {
        $expected = [
            'role' => 'heading',
        ];

        $this->heading->setRole('heading');

        $this->assertJsonStringEqualsJsonString(json_encode($expected), json_encode($this->heading));
    }
}
