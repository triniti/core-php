<?php
declare(strict_types=1);

namespace Triniti\Tests\AppleNews\Scene;

use PHPUnit\Framework\TestCase;
use Triniti\AppleNews\Scene\FadingStickyHeader;

class FadingStickyHeaderTest extends TestCase
{
    protected FadingStickyHeader $fadingStickyHeader;

    public function setUp(): void
    {
        $this->fadingStickyHeader = new FadingStickyHeader();
    }

    public function testCreateFadingStickyHeader(): void
    {
        $this->assertInstanceOf(
            'Triniti\AppleNews\Scene\FadingStickyHeader',
            $this->fadingStickyHeader
        );

        $this->assertEquals('#000000', $this->fadingStickyHeader->getFadeColor(), 'fadeColor default value should be "#000000"');
    }

    public function testSetFadeColor(): void
    {
        $this->assertEquals('#000000', $this->fadingStickyHeader->getFadeColor(), 'fadeColor default value should be "#000000"');

        $this->fadingStickyHeader->setFadeColor('#123456');
        $this->assertEquals('#123456', $this->fadingStickyHeader->getFadeColor(), 'fadeColor should become "#123456"');
    }

    public function testJsonSerialize(): void
    {
        $expected = [
            'type'      => 'fading_sticky_header',
            'fadeColor' => '#000000',
        ];
        $this->assertJsonStringEqualsJsonString(json_encode($expected), json_encode($this->fadingStickyHeader));

        $expected['fadeColor'] = '#FFFFFF';
        $this->fadingStickyHeader->setFadeColor('#FFFFFF');
        $this->assertJsonStringEqualsJsonString(json_encode($expected), json_encode($this->fadingStickyHeader));
    }
}
