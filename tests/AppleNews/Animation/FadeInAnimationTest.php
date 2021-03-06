<?php
declare(strict_types=1);

namespace Triniti\Tests\AppleNews\Animation;

use Assert\AssertionFailedException;
use PHPUnit\Framework\TestCase;
use Triniti\AppleNews\Animation\FadeInAnimation;

class FadeInAnimationTest extends TestCase
{
    protected FadeInAnimation $fadeInAnimation;

    public function setUp(): void
    {
        $this->fadeInAnimation = new FadeInAnimation();
    }

    public function testCreateFadeInAnimation(): void
    {
        $this->assertInstanceOf('Triniti\AppleNews\Animation\FadeInAnimation',
            $this->fadeInAnimation);

        $this->assertSame(0.3, $this->fadeInAnimation->getInitialAlpha());
        $this->assertFalse($this->fadeInAnimation->getUserControllable());
    }

    public function testInitialAlphaValidation(): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->fadeInAnimation->setInitialAlpha(1.2);
    }

    public function testSetInitialAlphaAsNull(): void
    {
        $this->fadeInAnimation->setInitialAlpha();

        $this->assertNull($this->fadeInAnimation->getInitialAlpha());
    }

    public function testJsonSerialize(): void
    {
        $expectedDefault = [
            'userControllable' => false,
            'type'             => 'fade_in',
            'initialAlpha'     => 0.3,
        ];
        $this->assertJsonStringEqualsJsonString(json_encode($expectedDefault), json_encode($this->fadeInAnimation));

        $this->fadeInAnimation->setInitialAlpha(0.8);
        $expected = [
            'userControllable' => false,
            'type'             => 'fade_in',
            'initialAlpha'     => 0.8,
        ];
        $this->assertJsonStringEqualsJsonString(json_encode($expected), json_encode($this->fadeInAnimation));
    }
}


