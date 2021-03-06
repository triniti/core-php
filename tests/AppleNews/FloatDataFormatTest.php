<?php
declare(strict_types=1);

namespace Triniti\Tests\AppleNews;

use Assert\AssertionFailedException;
use PHPUnit\Framework\TestCase;
use Triniti\AppleNews\FloatDataFormat;

class FloatDataFormatTest extends TestCase
{
    public function testInit(): void
    {
        $floatDataFormat = new FloatDataFormat();
        $this->assertInstanceOf('Triniti\AppleNews\FloatDataFormat', $floatDataFormat);
        $this->assertNull($floatDataFormat->getDecimals());

        $floatDataFormat = new FloatDataFormat(3);
        $this->assertSame(3, $floatDataFormat->getDecimals());
    }

    public function testSetDecimals(): void
    {
        $floatDataFormat = new FloatDataFormat();
        $this->assertNull($floatDataFormat->getDecimals());

        $floatDataFormat->setDecimals(5);
        $this->assertSame(5, $floatDataFormat->getDecimals());

        $floatDataFormat->setDecimals();
        $this->assertSame(null, $floatDataFormat->getDecimals(), 'Decimals should be set to "null" when value is ommited');
    }

    public function testSetDecimalsInvalid(): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage('Decimal number should be greater than 0');
        $floatDataFormat = new FloatDataFormat();
        $floatDataFormat->setDecimals(-1);
    }

    public function testJsonSerialize(): void
    {
        $excepted = [
            'type'     => 'float',
            'decimals' => 3,
        ];

        $floatFormat = new FloatDataFormat(3);
        $this->assertJsonStringEqualsJsonString(json_encode($excepted), json_encode($floatFormat));
    }
}
