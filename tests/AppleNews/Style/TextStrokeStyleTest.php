<?php
declare(strict_types=1);

namespace Triniti\Tests\AppleNews\Style;

use PHPUnit\Framework\TestCase;
use Triniti\AppleNews\Style\TextStrokeStyle;

class TextStrokeStyleTest extends TestCase
{
    protected TextStrokeStyle $textStrokeStyle;

    public function setUp(): void
    {
        $this->textStrokeStyle = new TextStrokeStyle();
    }

    public function testCreateTextStrokeStyle(): void
    {
        $this->assertInstanceOf('Triniti\AppleNews\Style\TextStrokeStyle', $this->textStrokeStyle);
    }

    public function testJsonSerialize(): void
    {
        $this->textStrokeStyle->setColor('red');
        $this->textStrokeStyle->setWidth(1);
        $expectedJson = '{"color":"red","width":1}';
        $this->assertJsonStringEqualsJsonString($expectedJson, json_encode($this->textStrokeStyle));
    }
}



