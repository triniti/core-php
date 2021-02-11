<?php
declare(strict_types=1);

namespace Triniti\Tests\AppleNews\Link;

use Assert\AssertionFailedException;
use PHPUnit\Framework\TestCase;
use Triniti\AppleNews\Link\ComponentLink;

class ComponentLinkTest extends TestCase
{
    protected ComponentLink $componentLink;

    protected function setup(): void
    {
        $this->componentLink = new ComponentLink();
    }

    public function testCreateComponentLink(): void
    {
        $this->assertInstanceOf(
            'Triniti\AppleNews\Link\ComponentLink',
            $this->componentLink
        );
    }

    public function testSetURLValid(): void
    {
        $this->assertNull($this->componentLink->getURL());

        $url = 'https://example.news/A5vHgPPmQSvuIxPjeXLTdGQ#Text';
        $this->componentLink->setURL($url);
        $this->assertEquals($url, $this->componentLink->getURL());
    }

    public function testSetURLInvalid(): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage('Invalid URL');
        $this->assertNull($this->componentLink->getURL());

        $this->componentLink->setURL('http-invalid');
    }

    public function testJsonSerialize(): void
    {
        $expected = [
            'type' => 'link',
            'URL'  => 'http://www.example.com',
        ];

        $this->componentLink->setURL('http://www.example.com');
        $this->assertJsonStringEqualsJsonString(json_encode($expected), json_encode($this->componentLink));
    }

    public function testValidate(): void
    {
        try {
            $this->componentLink->setURL('http://www.example.com');
            $this->componentLink->validate();
        } catch (\Assert\AssertionFailedException $afe) {
            $this->assertTrue(false);
        }

        $this->assertTrue(true, 'no exception should be throwed');
    }

    public function testValidateInvalid(): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage('URL is required');
        $this->componentLink->validate();
    }
}
