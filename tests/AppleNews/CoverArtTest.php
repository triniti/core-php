<?php
declare(strict_types=1);

namespace Triniti\Tests\AppleNews;

use PHPUnit\Framework\TestCase;
use Triniti\AppleNews\CoverArt;

class CoverArtTest extends TestCase
{
    protected CoverArt $coverArt;

    public function setUp(): void
    {
        $this->coverArt = new CoverArt();
    }

    public function testCreateCoverArtObject(): void
    {
        $this->assertInstanceOf('Triniti\AppleNews\CoverArt', $this->coverArt);
    }

    public function testSetURL(): void
    {
        $this->assertNull($this->coverArt->getURL());

        $this->coverArt->setURL('http://www.test.com');
        $this->assertSame('http://www.test.com', $this->coverArt->getURL());
    }

    public function testSetAccessibilityCaption(): void
    {
        $this->assertNull($this->coverArt->getAccessibilityCaption());

        $this->coverArt->setAccessibilityCaption('caption');
        $this->assertSame('caption', $this->coverArt->getAccessibilityCaption());
    }

    public function testJsonSerialize(): void
    {
        $this->coverArt->setURL('http://www.test.com')->setAccessibilityCaption('caption');
        $expectedJson = '{"type":"image","URL":"http://www.test.com","accessibilityCaption":"caption"}';
        $this->assertJsonStringEqualsJsonString($expectedJson, json_encode($this->coverArt));
    }
}
