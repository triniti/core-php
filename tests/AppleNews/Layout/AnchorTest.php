<?php
declare(strict_types=1);

namespace Triniti\Tests\AppleNews\Layout;

use Assert\AssertionFailedException;
use PHPUnit\Framework\TestCase;
use Triniti\AppleNews\Layout\Anchor;

class AnchorTest extends TestCase
{
    protected Anchor $anchor;

    public function setUp(): void
    {
        $this->anchor = new Anchor();
    }

    public function testCreateAnchorObject(): void
    {
        $this->assertInstanceOf('Triniti\AppleNews\Layout\Anchor', $this->anchor);

        $this->assertNull($this->anchor->getOriginAnchorPosition());
        $this->assertNull($this->anchor->getRangeLength());
        $this->assertNull($this->anchor->getRangeStart());
        $this->assertNull($this->anchor->getTarget());
        $this->assertNull($this->anchor->getTargetAnchorPosition());
        $this->assertNull($this->anchor->getTargetComponentIdentifier());
    }

    public function testSetters(): void
    {
        $this->anchor
            ->setOriginAnchorPosition('top')
            ->setRangeLength(10)
            ->setRangeStart(5)
            ->setTarget('html-elem-id')
            ->setTargetAnchorPosition('center')
            ->setTargetComponentIdentifier('test-id');

        $this->assertSame('top', $this->anchor->getOriginAnchorPosition());
        $this->assertSame(10, $this->anchor->getRangeLength());
        $this->assertSame(5, $this->anchor->getRangeStart());
        $this->assertSame('html-elem-id', $this->anchor->getTarget());
        $this->assertSame('center', $this->anchor->getTargetAnchorPosition());
        $this->assertSame('test-id', $this->anchor->getTargetComponentIdentifier());
    }

    public function testOriginAnchorPosition(): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->anchor->setOriginAnchorPosition('invalid_position');
    }

    public function testTargetAnchorPosition(): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->anchor->setOriginAnchorPosition('invalid_position');
    }

    public function testValidateTargetAnchorPositionIsrequired(): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->anchor->setTargetComponentIdentifier('a');

        $this->anchor->validate();
    }

    public function testValidateRangeValidation(): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage('if rangeStart is specified, rangeLength is required.');
        $this->anchor->setTargetAnchorPosition('top')->setRangeStart(9);

        $this->anchor->validate();
    }

    public function testJsonSerialize(): void
    {
        $this->anchor->setTargetComponentIdentifier('test');
        $expectedJson = '{"targetComponentIdentifier":"test"}';
        $this->assertJsonStringEqualsJsonString($expectedJson, json_encode($this->anchor));
    }
}

