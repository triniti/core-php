<?php
declare(strict_types=1);

namespace Triniti\Tests\AppleNews\Style;

use Assert\AssertionFailedException;
use PHPUnit\Framework\TestCase;
use Triniti\AppleNews\Style\Offset;
use Triniti\AppleNews\Style\Shadow;

class ShadowTest extends TestCase
{
    protected Shadow $shadow;

    public function setUp(): void
    {
        $this->shadow = new Shadow();
    }

    public function testCreateshadow(): void
    {
        $this->assertInstanceOf('Triniti\AppleNews\Style\Shadow', $this->shadow);
    }

    public function testGetSetColor(): void
    {
        $this->assertNull($this->shadow->getColor());
        $this->shadow->setColor('blue');
        $this->assertSame('blue', $this->shadow->getColor());
    }

    public function testSetOpacity(): void
    {
        $this->assertNull($this->shadow->getOpacity());

        $this->shadow->setOpacity(0.8);
        $this->assertSame(0.8, $this->shadow->getOpacity());
    }

    public function testSetOpacityInvalid(): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->shadow->setOpacity(1.1);
    }

    public function testSetRadius(): void
    {
        $this->assertNull($this->shadow->getRadius());

        $this->shadow->setRadius(90);
        $this->assertSame(90, $this->shadow->getRadius());
    }

    public function testSetRadiusInvalid(): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->shadow->setRadius(101);
    }

    public function testSetOffset(): void
    {
        $this->assertNull($this->shadow->getOffset());

        $offset = new Offset();
        $offset->setX(30)->setY(-30);
        $this->shadow->setOffset($offset);
        $this->assertSame($offset, $this->shadow->getOffset());
    }

    public function testSetOffsetInvalid(): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->shadow->setOffset(new Offset());
    }

    public function testValidate(): void
    {
        $this->shadow
            ->setRadius(50)
            ->setColor('#333333');

        try {
            $this->shadow->validate();
        } catch (AssertionFailedException $afe) {
            $this->assertTrue(false);
        }

        $this->assertTrue(true, 'no exception should be thrown');
    }

    public function testValidationInvalid(): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->shadow->setOpacity(0.3)->setColor('#111111');
        $this->shadow->validate();
    }

    public function testJsonSerialize(): void
    {
        $this->shadow->setColor('red');
        $this->shadow->setRadius(50);
        $this->shadow->setOpacity();
        $expectedJson = '{"color":"red","radius":50,"opacity":1}';
        $this->assertJsonStringEqualsJsonString($expectedJson, json_encode($this->shadow));
    }

}



