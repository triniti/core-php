<?php
declare(strict_types=1);

namespace Triniti\Tests\AppleNews\Animation;

use Assert\AssertionFailedException;
use PHPUnit\Framework\TestCase;
use Triniti\AppleNews\Animation\ScaleFadeAnimation;

class ScaleFadeAnimationTest extends TestCase
{
    protected ScaleFadeAnimation $scaleFadeAnimation;

    public function setUp(): void
    {
        $this->scaleFadeAnimation = new ScaleFadeAnimation();
    }

    public function testCreateMoveInAnimation(): void
    {
        $this->assertInstanceOf('Triniti\AppleNews\Animation\ScaleFadeAnimation',
            $this->scaleFadeAnimation);

        $this->assertSame(0.3, $this->scaleFadeAnimation->getInitialAlpha());
        $this->assertSame(0.75, $this->scaleFadeAnimation->getInitialScale());
    }


    public function textSetInitialAlpha()
    {
        $this->expectException(AssertionFailedException::class);
        $this->scaleFadeAnimation->setInitialAlpha(0.5);
        $this->assertSame(0.5, $this->scaleFadeAnimation->getInitialAlpha());

        $this->scaleFadeAnimation->setInitialAlpha();
        $this->assertNull($this->scaleFadeAnimation->getInitialAlpha());

        $this->scaleFadeAnimation->setInitialAlpha(2.0);
    }

    public function textSetInitialScale()
    {
        $this->expectException(AssertionFailedException::class);
        $this->scaleFadeAnimation->setInitialScale(0.5);
        $this->assertSame(0.5, $this->scaleFadeAnimation->getInitialScale());

        $this->scaleFadeAnimation->setInitialScale();
        $this->assertNull($this->scaleFadeAnimation->getInitialScale());

        $this->scaleFadeAnimation->setInitialScale(2.0);
    }

    public function testJsonSerialize(): void
    {
        $expectedDefault = [
            'userControllable' => false,
            'type'             => 'scale_fade',
            'initialAlpha'     => 0.3,
            'initialScale'     => 0.75,
        ];
        $this->assertJsonStringEqualsJsonString(json_encode($expectedDefault), json_encode($this->scaleFadeAnimation));

        $this->scaleFadeAnimation->setInitialAlpha();
        $this->scaleFadeAnimation->setInitialScale();
        $expected = [
            'userControllable' => false,
            'type'             => 'scale_fade',
        ];
        $this->assertJsonStringEqualsJsonString(json_encode($expected), json_encode($this->scaleFadeAnimation));
    }
}
