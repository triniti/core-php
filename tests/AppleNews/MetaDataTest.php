<?php

namespace Triniti\Tests\AppleNews\Layout;

use Assert\AssertionFailedException;
use PHPUnit\Framework\TestCase;
use Triniti\AppleNews\CoverArt;
use Triniti\AppleNews\LinkedArticle;
use Triniti\AppleNews\Metadata;
use Triniti\Tests\AppleNews\AbstractPbjxTest;

class MetaDataTest extends TestCase
{
    protected Metadata $metadata;

    public function setUp(): void
    {
        $this->metadata = new Metadata();
    }

    public function testCreateMetadata(): void
    {
        $this->assertInstanceOf(
            'Triniti\AppleNews\Metadata',
            $this->metadata
        );
    }

    public function testGetSetAuthors(): void
    {
        $this->metadata->setAuthors(['author1', 'author2']);
        $this->assertEquals(['author1', 'author2'], $this->metadata->getAuthors());

        $this->metadata->setAuthors();
        $this->assertEmpty($this->metadata->getAuthors());
    }

    public function testSetCampaignData(): void
    {
        $campaignData = [
            "categories" => [
                "exclusive",
                "celebrity-justice",
                "fashion",
                "celebrity-death",
                "rip",
                "money",
            ],
            "sponsor"    => [
                "test",
            ],
        ];

        $this->metadata->setCampaignData($campaignData);
        $this->assertEquals($campaignData, $this->metadata->getCampaignData());

        $this->metadata->setCampaignData();
        $this->assertEmpty($this->metadata->getCampaignData());
    }

    public function testGetSetCanonicalUrl(): void
    {
        $this->metadata->setCanonicalURL('http://test.com');
        $this->assertEquals('http://test.com', $this->metadata->getCanonicalURL());

        $this->metadata->setCanonicalURL();
        $this->assertNull($this->metadata->getCanonicalURL());

        $this->metadata->setCanonicalURL(null);
        $this->assertNull($this->metadata->getCanonicalURL());
    }

    public function testSetInvalidCanonicalUrl(): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->metadata->setCanonicalURL('test');
    }

    public function testGetSetAddCoverArt(): void
    {
        $coverArt1 = new CoverArt();
        $coverArt1->setURL('http://test.com');

        $coverArt2 = new CoverArt();
        $coverArt2->setURL('http://test2.com');

        $coverArt[] = $coverArt1;

        $this->metadata->setCoverArts($coverArt);
        $this->assertEquals($coverArt, $this->metadata->getCoverArt());

        $coverArt[] = $coverArt2;
        $this->metadata->addCoverArts($coverArt);
        $this->assertEquals([$coverArt1, $coverArt1, $coverArt2], $this->metadata->getCoverArt());

        $this->metadata->addCoverArts();
        $this->assertEquals([$coverArt1, $coverArt1, $coverArt2], $this->metadata->getCoverArt());

        $this->metadata->setCoverArts();
        $this->assertEquals([], $this->metadata->getCoverArt());
    }

    public function testGetSetDateCreated(): void
    {
        $this->metadata->setDateCreated('test');
        $this->assertEquals('test', $this->metadata->getDateCreated());

        $this->metadata->setDateCreated();
        $this->assertNull($this->metadata->getDateCreated());

        $this->metadata->setDateCreated(null);
        $this->assertNull($this->metadata->getDateCreated());
    }

    public function testGetSetDateModified(): void
    {
        $this->metadata->setDateModified('test');
        $this->assertEquals('test', $this->metadata->getDateModified());

        $this->metadata->setDateModified();
        $this->assertNull($this->metadata->getDateModified());

        $this->metadata->setDateModified(null);
        $this->assertNull($this->metadata->getDateModified());
    }

    public function testGetSetDatePublished(): void
    {
        $this->metadata->setDatePublished('test');
        $this->assertEquals('test', $this->metadata->getDatePublished());

        $this->metadata->setDatePublished();
        $this->assertNull($this->metadata->getDatePublished());

        $this->metadata->setDatePublished(null);
        $this->assertNull($this->metadata->getDatePublished());
    }

    public function testGetSetExcerpt(): void
    {
        $this->metadata->setExcerpt('test');
        $this->assertEquals('test', $this->metadata->getExcerpt());

        $this->metadata->setExcerpt();
        $this->assertNull($this->metadata->getExcerpt());

        $this->metadata->setExcerpt(null);
        $this->assertNull($this->metadata->getExcerpt());
    }

    public function testGetSetGeneratorIdentifier(): void
    {
        $this->metadata->setGeneratorIdentifier('test');
        $this->assertEquals('test', $this->metadata->getGeneratorIdentifier());
    }

    public function testGetSetGeneratorName(): void
    {
        $this->metadata->setGeneratorName('test');
        $this->assertEquals('test', $this->metadata->getGeneratorName());
    }

    public function testGetSetGeneratorVersion(): void
    {
        $this->metadata->setGeneratorVersion('test');
        $this->assertEquals('test', $this->metadata->getGeneratorVersion());
    }

    public function testGetSetKeywords(): void
    {
        $this->metadata->setKeywords(['test', 'test2']);
        $this->assertEquals(['test', 'test2'], $this->metadata->getKeywords());
    }

    public function testGetSetInvalidKeywords(): void
    {
        $this->expectException(AssertionFailedException::class);
        for ($i = 0; $i <= 60; $i++) {
            $keywords[] = 'test' . $i;
        }

        $this->metadata->setKeywords($keywords);
    }

    public function testGetSetAddLinks(): void
    {
        $link1 = new LinkedArticle();
        $link1
            ->setURL('http://test.com')
            ->setRelationship('related');


        $link2 = new LinkedArticle();
        $link2
            ->setURL('http://test2.com')
            ->setRelationship('related');

        $links[] = $link1;

        $this->metadata->setLinks($links);
        $this->assertEquals($links, $this->metadata->getLinks());

        $links[] = $link2;
        $this->metadata->addLinks($links);
        $this->assertEquals([$link1, $link1, $link2], $this->metadata->getLinks());

        $this->metadata->addLinks();
        $this->assertEquals([$link1, $link1, $link2], $this->metadata->getLinks());

        $this->metadata->setLinks();
        $this->assertEquals([], $this->metadata->getLinks());
    }

    public function testGetSetThumbnailUrl(): void
    {
        $this->metadata->setThumbnailURL('http://test.com');
        $this->assertEquals('http://test.com', $this->metadata->getThumbnailURL());

        $this->metadata->setThumbnailURL();
        $this->assertNull($this->metadata->getThumbnailURL());

        $this->metadata->setThumbnailURL(null);
        $this->assertNull($this->metadata->getThumbnailURL());
    }

    public function testSetInvalidThumbnailUrl(): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->metadata->setThumbnailURL('test');
    }

    public function testGetSetTransparentToolbar(): void
    {
        $this->metadata->setTransparentToolbar(true);
        $this->assertTrue($this->metadata->getTransparentToolbar());

        $this->metadata->setTransparentToolbar();
        $this->assertTrue($this->metadata->getTransparentToolbar());
    }

    public function testGetSetVideoUrl(): void
    {
        $this->metadata->setVideoURL('http://test.com');
        $this->assertEquals('http://test.com', $this->metadata->getVideoURL());

        $this->metadata->setVideoURL();
        $this->assertNull($this->metadata->getVideoURL());

        $this->metadata->setVideoURL(null);
        $this->assertNull($this->metadata->getVideoURL());
    }

    public function testSetInvalidVideoUrl(): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->metadata->setThumbnailURL('test');
    }

    public function testJsonSerialize(): void
    {
        $campaignData = [
            "categories" => [
                "exclusive",
                "celebrity-justice",
                "fashion",
                "celebrity-death",
                "rip",
                "money",
            ],
            "sponsor"    => [
                "test",
            ],
        ];

        $coverArt1 = new CoverArt();
        $coverArt1->setURL('http://test.com');

        $link1 = new LinkedArticle();
        $link1
            ->setURL('http://test.com')
            ->setRelationship('related');


        $expected = [
            'authors'             => ['author1'],
            'campaignData'        => $campaignData,
            'canonicalURL'        => 'http://test.com',
            'coverArt'            => [$coverArt1],
            'dateCreated'         => 'dateCreated',
            'dateModified'        => 'dateModified',
            'datePublished'       => 'datePublished',
            'excerpt'             => 'excerpt',
            'generatorIdentifier' => 'TrinitiAppleNewsPlugin',
            'generatorName'       => 'Triniti Apple News Plugin',
            'generatorVersion'    => '0.1',
            'keywords'            => ['keywords'],
            'links'               => [$link1],
            'thumbnailURL'        => 'http://test.com',
            'transparentToolbar'  => true,
            'videoURL'            => 'http://test.com',
        ];

        $this->metadata
            ->setAuthors(['author1'])
            ->setCampaignData($campaignData)
            ->setCanonicalURL('http://test.com')
            ->setCoverArts([$coverArt1])
            ->setDateCreated('dateCreated')
            ->setDateModified('dateModified')
            ->setDatePublished('datePublished')
            ->setExcerpt('excerpt')
            ->setGeneratorIdentifier('TrinitiAppleNewsPlugin')
            ->setGeneratorName('Triniti Apple News Plugin')
            ->setGeneratorVersion('0.1')
            ->setKeywords(['keywords'])
            ->setLinks([$link1])
            ->setThumbnailURL('http://test.com')
            ->setTransparentToolbar(true)
            ->setVideoURL('http://test.com');

        $this->assertJsonStringEqualsJsonString(json_encode($expected), json_encode($this->metadata));
    }
}
