<?php
declare(strict_types=1);

namespace Triniti\Tests\AppleNews\Style;

use PHPUnit\Framework\TestCase;
use Triniti\AppleNews\Style\LinearGradientFill;

class LinearGradientFillTest extends TestCase
{
    public function testGetSetAngle(): void
    {
        $linearGradientFill = new LinearGradientFill();
        $this->assertNull($linearGradientFill->getAngle());
        $linearGradientFill->setAngle(20);
        $this->assertSame(20, $linearGradientFill->getAngle());
    }
}
