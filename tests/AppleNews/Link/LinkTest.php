<?php
declare(strict_types=1);

namespace Triniti\Tests\AppleNews\Link;

use Assert\AssertionFailedException;
use PHPUnit\Framework\TestCase;
use Triniti\AppleNews\Link\Link;

class LinkTest extends TestCase
{
    protected Link $link;

    protected function setup(): void
    {
        $this->link = new Link();
    }

    public function testCreateLink(): void
    {
        $this->assertInstanceOf(
            'Triniti\AppleNews\Link\Link',
            $this->link
        );
    }

    public function testSetRangeLength(): void
    {
        $this->assertNull($this->link->getRangeLength());

        $this->link->setRangeLength(1);
        $this->assertEquals(1, $this->link->getRangeLength());
    }

    public function testSetRangeStart(): void
    {
        $this->assertNull($this->link->getRangeStart());

        $this->link->setRangeStart(1);
        $this->assertEquals(1, $this->link->getRangeStart());
    }

    public function testSetURLValid(): void
    {
        $this->assertNull($this->link->getURL());

        $url = 'https://example.news/A5vHgPPmQSvuIxPjeXLTdGQ#Text';
        $this->link->setURL($url);
        $this->assertEquals($url, $this->link->getURL());
    }

    public function testSetURLInvalid(): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage('Invalid URL');
        $this->assertNull($this->link->getURL());

        $this->link->setURL('http-invalid');
    }

    public function testValidate(): void
    {
        $this->link->setURL('http://www.example.com')->setRangeStart(0)->setRangeLength(20);

        try {
            $this->link->validate();
        } catch (\Assert\AssertionFailedException $afe) {
            $this->assertTrue(false);
        }

        $this->assertTrue(true);
    }

    public function testValidateInvalidFromParent(): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage('rangeStart is required');
        $this->link->setURL('http://www.example.com')->setRangeLength(20);
        $this->link->validate();
    }

    public function testValidateInvalid(): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage('URL is required');
        $this->link->setRangeStart(1)->setRangeLength(20);
        $this->link->validate();
    }

    public function testJsonSerialize(): void
    {
        $expected = [
            'type'        => 'link',
            'rangeStart'  => 1,
            'rangeLength' => 10,
            'URL'         => 'http://www.example.com',
        ];

        $this->link->setURL('http://www.example.com')->setRangeStart(1)->setRangeLength(10);
        $this->assertJsonStringEqualsJsonString(json_encode($expected), json_encode($this->link));
    }
}
