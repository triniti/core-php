<?php
declare(strict_types=1);

namespace Triniti\Tests\AppleNews\Style;

use Assert\AssertionFailedException;
use PHPUnit\Framework\TestCase;
use Triniti\AppleNews\Style\VideoFill;

class VideoFillTest extends TestCase
{
    protected VideoFill $videoFill;

    public function setUp(): void
    {
        $this->videoFill = new VideoFill();
    }

    public function testCreateVideoFill(): void
    {
        $this->markTestIncomplete();
        $this->assertInstanceOf('Triniti\AppleNews\Style\VideoFill', $this->videoFill);
        $this->assertNull($this->videoFill->getLoop());
        $this->assertNull($this->videoFill->getStillURL());
        $this->assertNull($this->videoFill->getFillMode());
        $this->assertNull($this->videoFill->getURL());
        $this->assertNull($this->videoFill->getHorizontalAlignment());
        $this->assertNull($this->videoFill->getAttachment());
        $this->assertNull($this->videoFill->getVerticalAlignment());
    }

    public function testSetAttachment(): void
    {
        $this->videoFill->setAttachment();
        $this->assertEquals('scroll', $this->videoFill->getAttachment());

        $this->videoFill->setAttachment('fixed');
        $this->assertEquals('fixed', $this->videoFill->getAttachment());
    }

    public function testSetAttachmentInvalid(): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->videoFill->setAttachment('invalid');
    }

    public function testSetURL(): void
    {
        $this->videoFill->setURL('http://www.example.com');
        $this->assertEquals('http://www.example.com', $this->videoFill->getURL());
    }

    public function testSetStillURL(): void
    {
        $this->videoFill->setStillURL('bundle://test.jpg');
        $this->assertEquals('bundle://test.jpg', $this->videoFill->getStillURL());
    }

    public function testSetLoop(): void
    {
        $this->videoFill->setLoop();
        $this->assertTrue($this->videoFill->getLoop());

        $this->videoFill->setLoop(false);
        $this->assertFalse($this->videoFill->getLoop());

        $this->videoFill->setLoop(true);
        $this->assertTrue($this->videoFill->getLoop());
    }

    public function testSetFillMode(): void
    {
        $this->videoFill->setFillMode();
        $this->assertEquals('cover', $this->videoFill->getFillMode());

        $this->videoFill->setFillMode('fit');
        $this->assertEquals('fit', $this->videoFill->getFillMode());

        $this->videoFill->setFillMode('cover');
        $this->assertEquals('cover', $this->videoFill->getFillMode());
    }

    public function testSetFillModeInvalid(): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->videoFill->setFillMode('abc-invalid');
    }

    public function testSetHorizontalAlignment(): void
    {
        $this->videoFill->setHorizontalAlignment();
        $this->assertEquals('center', $this->videoFill->getHorizontalAlignment());

        $this->videoFill->setHorizontalAlignment('left');
        $this->assertEquals('left', $this->videoFill->getHorizontalAlignment());

        $this->videoFill->setHorizontalAlignment('right');
        $this->assertEquals('right', $this->videoFill->getHorizontalAlignment());

        $this->videoFill->setHorizontalAlignment('center');
        $this->assertEquals('center', $this->videoFill->getHorizontalAlignment());
    }

    public function testSetHorizontalAlignmentInvalid(): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->videoFill->setHorizontalAlignment('abc');
    }

    public function testSetVerticalAlignment(): void
    {
        $this->videoFill->setVerticalAlignment();
        $this->assertEquals('center', $this->videoFill->getVerticalAlignment());

        $this->videoFill->setVerticalAlignment('top');
        $this->assertEquals('top', $this->videoFill->getVerticalAlignment());

        $this->videoFill->setVerticalAlignment('bottom');
        $this->assertEquals('bottom', $this->videoFill->getVerticalAlignment());

        $this->videoFill->setVerticalAlignment('center');
        $this->assertEquals('center', $this->videoFill->getVerticalAlignment());
    }

    public function testSetVerticalAlignmentInvalid(): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->videoFill->setVerticalAlignment('abc');
    }

    public function testJsonSerialize(): void
    {
        $this->videoFill
            ->setVerticalAlignment('top')
            ->setURL('https://live-streaming.apple.com/hls/2014/fded0-1077dae/main.m3u8')
            ->setStillURL('bundle://video-still.jpg')
            ->setFillMode('cover');

        $expected = [
            'type'              => 'video',
            'URL'               => 'https://live-streaming.apple.com/hls/2014/fded0-1077dae/main.m3u8',
            'fillMode'          => 'cover',
            'stillURL'          => 'bundle://video-still.jpg',
            'verticalAlignment' => 'top',
        ];

        $this->assertJsonStringEqualsJsonString(json_encode($expected), json_encode($this->videoFill));
    }

    public function testValidate(): void
    {
        $this->videoFill->setURL('http://www.example.com')->setStillURL('http://www.example.com');
        try {
            $this->videoFill->validate();
        } catch (\Assert\AssertionFailedException $afe) {
            $this->assertTrue(false);
        }

        $this->assertTrue(true, 'No exception should be throwed');
    }

    public function testValidationInvalid(): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->videoFill->setFillMode('fit');
        $this->videoFill->validate();
    }
}
