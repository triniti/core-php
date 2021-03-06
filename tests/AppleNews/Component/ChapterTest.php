<?php

namespace Triniti\Tests\AppleNews\Layout;

use PHPUnit\Framework\TestCase;
use Triniti\AppleNews\Component\Chapter;
use Triniti\AppleNews\Scene\ParallaxScaleHeader;

class ChapterTest extends TestCase
{
    protected Chapter $chapter;

    public function setUp(): void
    {
        $this->chapter = new Chapter();
    }

    public function testCreateChapter(): void
    {
        $this->assertInstanceOf(
            'Triniti\AppleNews\Component\Chapter',
            $this->chapter
        );
    }

    public function testGetSetScene(): void
    {
        $scene = new ParallaxScaleHeader();

        $this->chapter->setScene($scene);
        $this->assertEquals($scene, $this->chapter->getScene());

        $this->chapter->setScene();
        $this->assertNull($this->chapter->getScene());
    }

    public function testJsonSerialize(): void
    {
        $expected = [
            'role'  => 'chapter',
            'scene' => new ParallaxScaleHeader(),
        ];

        $this->chapter->setScene(new ParallaxScaleHeader());

        $this->assertJsonStringEqualsJsonString(json_encode($expected), json_encode($this->chapter));
    }
}
