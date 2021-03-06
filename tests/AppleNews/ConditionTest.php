<?php
declare(strict_types=1);

namespace Triniti\Tests\AppleNews\Style;

use Assert\AssertionFailedException;
use PHPUnit\Framework\TestCase;
use Triniti\AppleNews\Condition;
use Triniti\Tests\AppleNews\AbstractPbjxTest;

class ConditionTest extends TestCase
{
    protected Condition $condition;

    public function setUp(): void
    {
        $this->condition = new Condition();
    }

    public function testCreateCondition(): void
    {
        $this->assertInstanceOf(
            'Triniti\AppleNews\Condition',
            $this->condition
        );
    }

    public function testSetHorizontalSizeClass(): void
    {
        $this->assertNull($this->condition->getHorizontalSizeClass());
        $this->condition->setHorizontalSizeClass('any');

        $this->assertSame('any', $this->condition->getHorizontalSizeClass());
    }

    public function testSetHorizontalSizeClassInvalid(): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->assertNull($this->condition->getHorizontalSizeClass());
        $this->condition->setHorizontalSizeClass('test');
    }

    public function testSetMaxColumns(): void
    {
        $this->assertNull($this->condition->getMaxColumns());
        $this->condition->setMaxColumns(1);

        $this->assertSame(1, $this->condition->getMaxColumns());
    }

    public function testSetMaxColumnsInvalid(): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->assertNull($this->condition->getMaxColumns());
        $this->condition->setMaxColumns(0);
    }

    public function testSetMaxContentSizeCategory(): void
    {
        $this->assertNull($this->condition->getMaxContentSizeCategory());
        $this->condition->setMaxContentSizeCategory('XL');

        $this->assertSame('XL', $this->condition->getMaxContentSizeCategory());
    }

    public function testSetMaxContentSizeCategoryInvalid(): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->assertNull($this->condition->getMaxContentSizeCategory());
        $this->condition->setMaxContentSizeCategory('test');
    }

    public function testSetMaxSpecVersion(): void
    {
        $this->assertNull($this->condition->getMaxSpecVersion());
        $this->condition->setMaxSpecVersion('test');

        $this->assertSame('test', $this->condition->getMaxSpecVersion());
    }

    public function testSetMaxViewportAspectRatio(): void
    {
        $this->assertNull($this->condition->getMaxViewportAspectRatio());
        $this->condition->setMaxViewportAspectRatio(1.0);

        $this->assertSame(1.0, $this->condition->getMaxViewportAspectRatio());
    }

    public function testSetMaxViewportAspectRatioInvalid(): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->assertNull($this->condition->getMaxViewportAspectRatio());
        $this->condition->setMaxViewportAspectRatio(-1.0);
    }

    public function testSetMaxViewportWidth(): void
    {
        $this->assertNull($this->condition->getMaxViewportWidth());
        $this->condition->setMaxViewportWidth(1);

        $this->assertSame(1, $this->condition->getMaxViewportWidth());
    }

    public function testSetMaxViewportWidthInvalid(): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->assertNull($this->condition->getMaxViewportWidth());
        $this->condition->setMaxViewportWidth(-1);
    }

    public function testSetMinColumns(): void
    {
        $this->assertNull($this->condition->getMinColumns());
        $this->condition->setMinColumns(1);

        $this->assertSame(1, $this->condition->getMinColumns());
    }

    public function testSetMinColumnsInvalid(): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->assertNull($this->condition->getMinColumns());
        $this->condition->setMinColumns(-1);
    }

    public function testSetMinContentSizeCategory(): void
    {
        $this->assertNull($this->condition->getMinContentSizeCategory());
        $this->condition->setMinContentSizeCategory('XL');

        $this->assertSame('XL', $this->condition->getMinContentSizeCategory());
    }

    public function testSetMinContentSizeCategoryInvalid(): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->assertNull($this->condition->getMinContentSizeCategory());
        $this->condition->setMinContentSizeCategory('test');
    }

    public function testSetMinSpecVersion(): void
    {
        $this->assertNull($this->condition->getMinSpecVersion());
        $this->condition->setMinSpecVersion('test');

        $this->assertSame('test', $this->condition->getMinSpecVersion());
    }

    public function testSetMinViewportAspectRatio(): void
    {
        $this->assertNull($this->condition->getMinViewportAspectRatio());
        $this->condition->setMinViewportAspectRatio(1.0);

        $this->assertSame(1.0, $this->condition->getMinViewportAspectRatio());
    }

    public function testSetMinViewportAspectRatioInvalid(): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->assertNull($this->condition->getMinViewportAspectRatio());
        $this->condition->setMinViewportAspectRatio(-1.0);
    }

    public function testSetMinViewportWidth(): void
    {
        $this->assertNull($this->condition->getMinViewportWidth());
        $this->condition->setMinViewportWidth(1);

        $this->assertSame(1, $this->condition->getMinViewportWidth());
    }

    public function testSetMinViewportWidthInvalid(): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->assertNull($this->condition->getMinViewportWidth());
        $this->condition->setMinViewportWidth(-1);
    }

    public function testSetPlatform(): void
    {
        $this->assertNull($this->condition->getPlatform());
        $this->condition->setPlatform('any');

        $this->assertSame('any', $this->condition->getPlatform());
    }

    public function testSetPlatformInvalid(): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->assertNull($this->condition->getPlatform());
        $this->condition->setPlatform('test');
    }

    public function testSetPreferredColorScheme(): void
    {
        $this->assertNull($this->condition->getPreferredColorScheme());
        $this->condition->setPreferredColorScheme('any');

        $this->assertSame('any', $this->condition->getPreferredColorScheme());
    }

    public function testSetPreferredColorSchemeInvalid(): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->assertNull($this->condition->getPreferredColorScheme());
        $this->condition->setPreferredColorScheme('test');
    }

    public function testSetSubscriptionStatus(): void
    {
        $this->assertNull($this->condition->getSubscriptionStatus());
        $this->condition->setSubscriptionStatus('bundle');

        $this->assertSame('bundle', $this->condition->getSubscriptionStatus());
    }

    public function testSetSubscriptionStatusInvalid(): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->assertNull($this->condition->getSubscriptionStatus());
        $this->condition->setSubscriptionStatus('test');
    }

    public function testSetVerticalSizeClass(): void
    {
        $this->assertNull($this->condition->getVerticalSizeClass());
        $this->condition->setVerticalSizeClass('any');

        $this->assertSame('any', $this->condition->getVerticalSizeClass());
    }

    public function testSetVerticalSizeClassInvalid(): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->assertNull($this->condition->getVerticalSizeClass());
        $this->condition->setVerticalSizeClass('test');
    }

    public function testSetViewLocation(): void
    {
        $this->assertNull($this->condition->getViewLocation());
        $this->condition->setViewLocation('any');

        $this->assertSame('any', $this->condition->getViewLocation());
    }

    public function testSetViewLocationInvalid(): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->assertNull($this->condition->getViewLocation());
        $this->condition->setViewLocation('test');
    }

    public function testJsonSerialize(): void
    {
        $condition = new Condition();
        $condition->setPlatform('any');

        $expected = [
            'platform' => 'any',
        ];

        $this->assertJsonStringEqualsJsonString(json_encode($expected), json_encode($condition));
    }
}
