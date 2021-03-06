<?php
declare(strict_types=1);

namespace Triniti\Tests\AppleNews;

use Assert\AssertionFailedException;
use PHPUnit\Framework\TestCase;
use Triniti\AppleNews\LinkedArticle;

class LinkedArticleTest extends TestCase
{
    protected LinkedArticle $linkedArticle;

    protected function setup(): void
    {
        $this->linkedArticle = new LinkedArticle();
    }

    public function testCreateLinkedArticle(): void
    {
        $this->assertInstanceOf(
            'Triniti\AppleNews\LinkedArticle',
            $this->linkedArticle
        );
    }

    public function testSetRelationship(): void
    {
        $this->assertNull($this->linkedArticle->getRelationship());

        $this->linkedArticle->setRelationship('related');
        $this->assertSame('related', $this->linkedArticle->getRelationship());

        $this->linkedArticle->setRelationship('promoted');
        $this->assertSame('promoted', $this->linkedArticle->getRelationship());
    }

    public function testSetRelationshipInvalid(): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->linkedArticle->setRelationship('a');
    }

    public function testSetURL(): void
    {
        $this->assertNull($this->linkedArticle->getURL());

        $this->linkedArticle->setURL('https://applenews.com/abc');
        $this->assertSame('https://applenews.com/abc', $this->linkedArticle->getURL());
    }

    public function testSetURLInvalid(): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->linkedArticle->setURL('test');
    }

    public function testJsonSerialize(): void
    {
        $excepted = [
            'URL'          => 'https://apple.news/AT6kNQslCQy6EE4bF8hpOoQ',
            'relationship' => 'related',
        ];

        $this->linkedArticle->setURL('https://apple.news/AT6kNQslCQy6EE4bF8hpOoQ')->setRelationship('related');
        $this->assertJsonStringEqualsJsonString(json_encode($excepted), json_encode($this->linkedArticle));
    }

    public function testValidateNoURL(): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->linkedArticle->setRelationship('related');
        $this->linkedArticle->validate();
    }

    public function testValidateNoRelationship(): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->linkedArticle->setURL('http:www.example.com');
        $this->linkedArticle->validate();
    }
}
