<?php
declare(strict_types=1);

namespace Triniti\Tests\AppleNews\Style;

use PHPUnit\Framework\TestCase;
use Triniti\AppleNews\Style\TextDecoration;

class TextDecorationTest extends TestCase
{
    protected TextDecoration $textDecoration;

    public function setUp(): void
    {
        $this->textDecoration = new TextDecoration();
    }

    public function testCreateTextDecoration(): void
    {
        $this->assertInstanceOf('Triniti\AppleNews\Style\TextDecoration', $this->textDecoration);
    }

    public function testSetColor(): void
    {
        $this->assertNull($this->textDecoration->getColor());

        $this->textDecoration->setColor('#123456FF');
        $this->assertSame('#123456FF', $this->textDecoration->getColor());
    }

    public function testJsonSerialize(): void
    {
        $this->textDecoration->setColor('red');
        $expectedJson = '{"color":"red"}';
        $this->assertJsonStringEqualsJsonString($expectedJson, json_encode($this->textDecoration));
    }
}



