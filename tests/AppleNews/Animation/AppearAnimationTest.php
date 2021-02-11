<?php
declare(strict_types=1);

namespace Triniti\Tests\AppleNews\Animation;

use PHPUnit\Framework\TestCase;
use Triniti\AppleNews\Animation\AppearAnimation;

class AppearAnimationTest extends TestCase
{
    protected AppearAnimation $appearAnimation;

    public function setUp(): void
    {
        $this->appearAnimation = new AppearAnimation();
    }

    public function testCreateAppearAnimation(): void
    {
        $this->assertInstanceOf('Triniti\AppleNews\Animation\AppearAnimation',
            $this->appearAnimation);

        $this->assertFalse($this->appearAnimation->getUserControllable());
    }

    public function testSetUserControllable(): void
    {
        $this->appearAnimation->setUserControllable(true);

        $this->assertTrue($this->appearAnimation->getUserControllable());
    }

    public function testJsonSerialize(): void
    {
        $expectedDefault = [
            'userControllable' => false,
            'type'             => 'appear',
        ];
        $this->assertJsonStringEqualsJsonString(json_encode($expectedDefault), json_encode($this->appearAnimation));

        $this->appearAnimation->setUserControllable(true);
        $expected = [
            'userControllable' => true,
            'type'             => 'appear',
        ];
        $this->assertJsonStringEqualsJsonString(json_encode($expected), json_encode($this->appearAnimation));
    }
}
