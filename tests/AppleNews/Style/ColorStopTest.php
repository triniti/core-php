<?php
declare(strict_types=1);

namespace Triniti\Tests\AppleNews\Style;

use Assert\InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Triniti\AppleNews\Style\ColorStop;

class ColorStopTest extends TestCase
{
    protected ColorStop $border;

    public function setUp(): void
    {
        $this->border = new ColorStop();
    }

    public function testValidate(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->border->validate();
    }

    public function testGetSetUrl(): void
    {
        $this->assertNull($this->border->getColor());
        $this->border->setColor('#000000');
        $this->assertEquals('#000000', $this->border->getColor());
    }

    public function testGetSetLocation(): void
    {
        $this->assertNull($this->border->getLocation());
        $this->border->setLocation(5);
        $this->assertEquals(5, $this->border->getLocation());
    }

    public function testSetLocationTooLow(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->border->setLocation(-1);
    }

    public function testSetLocationTooHigh(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->border->setLocation(101);
    }
}
