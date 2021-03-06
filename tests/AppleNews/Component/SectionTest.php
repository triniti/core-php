<?php

namespace Triniti\Tests\AppleNews\Layout;

use PHPUnit\Framework\TestCase;
use Triniti\AppleNews\Component\Section;
use Triniti\AppleNews\Scene\ParallaxScaleHeader;

class SectionTest extends TestCase
{
    protected Section $section;

    public function setup(): void
    {
        $this->section = new Section();
    }

    public function testCreateSection(): void
    {
        $this->assertInstanceOf(
            'Triniti\AppleNews\Component\Section',
            $this->section
        );
    }

    public function testGetSetScene(): void
    {
        $scene = new ParallaxScaleHeader();

        $this->section->setScene($scene);
        $this->assertEquals($scene, $this->section->getScene());

        $this->section->setScene();
        $this->assertNull($this->section->getScene());
    }

    public function testJsonSerialize(): void
    {
        $expected = [
            'role'  => 'section',
            'scene' => new ParallaxScaleHeader(),
        ];

        $this->section->setScene(new ParallaxScaleHeader());

        $this->assertJsonStringEqualsJsonString(json_encode($expected), json_encode($this->section));
    }
}
