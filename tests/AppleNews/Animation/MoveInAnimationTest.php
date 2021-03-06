<?php
declare(strict_types=1);

namespace Triniti\Tests\AppleNews\Animation;

use Assert\AssertionFailedException;
use PHPUnit\Framework\TestCase;
use Triniti\AppleNews\Animation\MoveInAnimation;

class MoveInAnimationTest extends TestCase
{
    protected MoveInAnimation $moveInAnimation;

    public function setUp(): void
    {
        $this->moveInAnimation = new MoveInAnimation();
    }

    public function testCreateMoveInAnimation(): void
    {
        $this->assertInstanceOf('Triniti\AppleNews\Animation\MoveInAnimation',
            $this->moveInAnimation);

        $this->assertFalse($this->moveInAnimation->getUserControllable());
        $this->assertNull($this->moveInAnimation->getPreferredStartingPosition());
    }

    public function testSetPreferredStartingPosition(): void
    {
        $this->moveInAnimation->setPreferredStartingPosition('left');
        $this->assertSame('left', $this->moveInAnimation->getPreferredStartingPosition());
        $this->moveInAnimation->setPreferredStartingPosition('right');
        $this->assertSame('right', $this->moveInAnimation->getPreferredStartingPosition());
        $this->moveInAnimation->setPreferredStartingPosition();
        $this->assertSame(null, $this->moveInAnimation->getPreferredStartingPosition());
    }

    public function testPreferredStartingPositionValidation(): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->moveInAnimation->setPreferredStartingPosition('not_left_or_right');
    }

    public function testJsonSerialize(): void
    {
        $expected = [
            'userControllable' => false,
            'type'             => 'move_in',
        ];
        $this->assertJsonStringEqualsJsonString(json_encode($expected), json_encode($this->moveInAnimation));
    }
}


