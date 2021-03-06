<?php
declare(strict_types=1);

namespace Triniti\Tests\AppleNews\Style;

use Assert\AssertionFailedException;
use PHPUnit\Framework\TestCase;
use Triniti\AppleNews\Style\ImageFill;

class ImageFillTest extends TestCase
{
    protected ImageFill $imageFill;

    public function setUp(): void
    {
        $this->imageFill = new ImageFill();
    }

    public function testCreateImageFill(): void
    {
        $this->assertInstanceOf('Triniti\AppleNews\Style\ImageFill', $this->imageFill);
        $this->assertNull($this->imageFill->getFillMode());
        $this->assertNull($this->imageFill->getURL());
        $this->assertNull($this->imageFill->getHorizontalAlignment());
        $this->assertNull($this->imageFill->getAttachment());
        $this->assertNull($this->imageFill->getVerticalAlignment());
    }

    public function testSetAttachment(): void
    {
        $this->imageFill->setAttachment();
        $this->assertEquals('scroll', $this->imageFill->getAttachment());

        $this->imageFill->setAttachment('fixed');
        $this->assertEquals('fixed', $this->imageFill->getAttachment());
    }

    public function testSetAttachmentInvalid(): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->imageFill->setAttachment('invalid');
    }

    public function testSetURL(): void
    {
        $this->imageFill->setURL('http://www.example.com');
        $this->assertEquals('http://www.example.com', $this->imageFill->getURL());
    }

    public function testSetFillMode(): void
    {
        $this->imageFill->setFillMode();
        $this->assertEquals('cover', $this->imageFill->getFillMode());

        $this->imageFill->setFillMode('fit');
        $this->assertEquals('fit', $this->imageFill->getFillMode());

        $this->imageFill->setFillMode('cover');
        $this->assertEquals('cover', $this->imageFill->getFillMode());
    }

    public function testSetFillModeInvalid(): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->imageFill->setFillMode('abc-invalid');
    }

    public function testSetHorizontalAlignment(): void
    {
        $this->imageFill->setHorizontalAlignment();
        $this->assertEquals('center', $this->imageFill->getHorizontalAlignment());

        $this->imageFill->setHorizontalAlignment('left');
        $this->assertEquals('left', $this->imageFill->getHorizontalAlignment());

        $this->imageFill->setHorizontalAlignment('right');
        $this->assertEquals('right', $this->imageFill->getHorizontalAlignment());

        $this->imageFill->setHorizontalAlignment('center');
        $this->assertEquals('center', $this->imageFill->getHorizontalAlignment());
    }

    public function testSetHorizontalAlignmentInvalid(): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->imageFill->setHorizontalAlignment('abc');
    }

    public function testSetVerticalAlignment(): void
    {
        $this->imageFill->setVerticalAlignment();
        $this->assertEquals('center', $this->imageFill->getVerticalAlignment());

        $this->imageFill->setVerticalAlignment('top');
        $this->assertEquals('top', $this->imageFill->getVerticalAlignment());

        $this->imageFill->setVerticalAlignment('bottom');
        $this->assertEquals('bottom', $this->imageFill->getVerticalAlignment());

        $this->imageFill->setVerticalAlignment('center');
        $this->assertEquals('center', $this->imageFill->getVerticalAlignment());
    }

    public function testSetVerticalAlignmentInvalid(): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->imageFill->setVerticalAlignment('abc');
    }

    public function testJsonSerialize(): void
    {
        $this->imageFill
            ->setVerticalAlignment('top')
            ->setURL('bundle://image.jpg')
            ->setFillMode('cover');

        $expected = [
            'type'              => 'image',
            'URL'               => 'bundle://image.jpg',
            'fillMode'          => 'cover',
            'verticalAlignment' => 'top',
        ];

        $this->assertJsonStringEqualsJsonString(json_encode($expected), json_encode($this->imageFill));
    }

    public function testValidate(): void
    {
        $this->imageFill->setURL('http://www.example.com');
        try {
            $this->imageFill->validate();
        } catch (\Assert\AssertionFailedException $afe) {
            $this->assertTrue(false);
        }

        $this->assertTrue(true, 'No exception should be throwed');
    }

    public function testValidationInvalid(): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->imageFill->setFillMode('fit');
        $this->imageFill->validate();
    }
}
