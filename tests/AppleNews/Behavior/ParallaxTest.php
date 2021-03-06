<?php
declare(strict_types=1);

namespace Triniti\Tests\AppleNews\Behavior;

use Assert\AssertionFailedException;
use PHPUnit\Framework\TestCase;
use Triniti\AppleNews\Behavior\Parallax;

class ParallaxTest extends TestCase
{
    protected Parallax $parallax;

    public function setUp(): void
    {
        $this->parallax = new Parallax();
    }

    public function testCreateParallax(): void
    {
        $this->assertInstanceOf('Triniti\AppleNews\Behavior\Parallax', $this->parallax);

        $this->assertSame(0.9, $this->parallax->getFactor());
    }

    public function testSetFactor(): void
    {
        $this->parallax->setFactor(2.0);
        $this->assertSame(2.0, $this->parallax->getFactor());

        $this->parallax->setFactor();
        $this->assertNull($this->parallax->getFactor());
    }

    public function testSetFactorUpperBoundry(): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->parallax->setFactor(2.1);
    }

    public function testSetFactorLowerBoundry(): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->parallax->setFactor(0.4);
    }

    public function testJsonSerialize(): void
    {
        $expected = [
            'type'   => 'parallax',
            'factor' => 0.9,
        ];
        $this->assertJsonStringEqualsJsonString(json_encode($expected), json_encode($this->parallax));
    }
}
