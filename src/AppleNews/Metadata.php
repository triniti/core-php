<?php
declare(strict_types=1);

namespace Triniti\AppleNews;

use Assert\Assertion;

/**
 * @link https://developer.apple.com/documentation/apple_news/metadata
 */
class Metadata extends AppleNewsObject
{
    /** @var string[] */
    protected $authors = [];

    /** @var array */
    protected $campaignData = [];

    /** @var string */
    protected $canonicalURL;

    /** @var CoverArt[] */
    protected $coverArt = [];

    /** @var string */
    protected $dateCreated;

    /** @var string */
    protected $dateModified;

    /** @var string */
    protected $datePublished;

    /** @var string */
    protected $excerpt;

    /** @var string */
    protected $generatorIdentifier = 'TrinitiAppleNewsPlugin';

    /** @var string */
    protected $generatorName = 'Triniti Apple News Plugin';

    /** @var string */
    protected $generatorVersion = '0.1';

    /** @var string[] */
    protected $keywords = [];

    /** @var LinkedArticle[] */
    protected $links = [];

    /** @var string */
    protected $thumbnailURL;

    /** @var boolean */
    protected $transparentToolbar;

    /** @var string */
    protected $videoURL;

    /**
     * @return string[]
     */
    public function getAuthors(): array
    {
        return $this->authors;
    }

    /**
     * @param array $authors
     *
     * @return static
     */
    public function setAuthors(array $authors = []): self
    {
        $this->authors = $authors;
        return $this;
    }

    /**
     * @return array
     */
    public function getCampaignData(): array
    {
        return $this->campaignData;
    }

    /**
     * @param array $campaignData
     *
     * @return static
     */
    public function setCampaignData(array $campaignData = []): self
    {
        $this->campaignData = $campaignData;
        return $this;
    }

    /**
     * @return string
     */
    public function getCanonicalURL(): ?string
    {
        return $this->canonicalURL;
    }

    /**
     * @param string $canonicalURL
     *
     * @return static
     */
    public function setCanonicalURL(?string $canonicalURL = null): self
    {
        if (is_string($canonicalURL)) {
            Assertion::url($canonicalURL);
        }

        $this->canonicalURL = $canonicalURL;
        return $this;
    }

    /**
     * @return CoverArt[]
     */
    public function getCoverArt(): array
    {
        return $this->coverArt;
    }

    /**
     * @param CoverArt[] $coverArts
     *
     * @return static
     */
    public function setCoverArts(?array $coverArts = []): self
    {
        $this->coverArt = [];

        if ($coverArts) {
            foreach ($coverArts as $coverArt) {
                $this->addCoverArt($coverArt);
            }
        }

        return $this;
    }

    /**
     * @param CoverArt $coverArt
     *
     * @return static
     */
    public function addCoverArt(?CoverArt $coverArt = null): self
    {
        if ($coverArt) {
            $coverArt->validate();
            $this->coverArt[] = $coverArt;
        }

        return $this;
    }

    /**
     * @param CoverArt[] $coverArts
     *
     * @return static
     */
    public function addCoverArts(?array $coverArts = []): self
    {
        if ($coverArts) {
            foreach ($coverArts as $coverArt) {
                $this->addCoverArt($coverArt);
            }
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getDateCreated(): ?string
    {
        return $this->dateCreated;
    }

    /**
     * @param string $dateCreated
     *
     * @return static
     */
    public function setDateCreated(?string $dateCreated = null): self
    {
        $this->dateCreated = $dateCreated;
        return $this;
    }

    /**
     * @return string
     */
    public function getDateModified(): ?string
    {
        return $this->dateModified;
    }

    /**
     * @param string $dateModified
     *
     * @return static
     */
    public function setDateModified(?string $dateModified = null): self
    {
        $this->dateModified = $dateModified;
        return $this;
    }

    /**
     * @return string
     */
    public function getDatePublished(): ?string
    {
        return $this->datePublished;
    }

    /**
     * @param string $datePublished
     *
     * @return static
     */
    public function setDatePublished(?string $datePublished = null): self
    {
        $this->datePublished = $datePublished;
        return $this;
    }

    /**
     * @return string
     */
    public function getExcerpt(): ?string
    {
        return $this->excerpt;
    }

    /**
     * @param string $excerpt
     *
     * @return static
     */
    public function setExcerpt(?string $excerpt = null): self
    {
        $this->excerpt = $excerpt;
        return $this;
    }

    /**
     * @return string
     */
    public function getGeneratorIdentifier(): string
    {
        return $this->generatorIdentifier;
    }

    /**
     * @param string $generatorIdentifier
     *
     * @return static
     */
    public function setGeneratorIdentifier(string $generatorIdentifier): self
    {
        $this->generatorIdentifier = $generatorIdentifier;
        return $this;
    }

    /**
     * @return string
     */
    public function getGeneratorName(): string
    {
        return $this->generatorName;
    }

    /**
     * @param string $generatorName
     *
     * @return static
     */
    public function setGeneratorName(string $generatorName): self
    {
        $this->generatorName = $generatorName;
        return $this;
    }

    /**
     * @return string
     */
    public function getGeneratorVersion(): string
    {
        return $this->generatorVersion;
    }

    /**
     * @param string $generatorVersion
     *
     * @return static
     */
    public function setGeneratorVersion(string $generatorVersion): self
    {
        $this->generatorVersion = $generatorVersion;
        return $this;
    }

    /**
     * @return string[]
     */
    public function getKeywords(): array
    {
        return $this->keywords;
    }

    /**
     * @param array $keywords
     *
     * @return static
     */
    public function setKeywords(?array $keywords = []): self
    {
        Assertion::lessOrEqualThan(count($keywords), 50, 'You can define up to 50 keywords');
        $this->keywords = $keywords;
        return $this;
    }

    /**
     * @return LinkedArticle[]
     */
    public function getLinks(): array
    {
        return $this->links;
    }

    /**
     * @param LinkedArticle $linkedArticle
     *
     * @return static
     */
    public function addLink(?LinkedArticle $linkedArticle = null): self
    {
        if (null === $linkedArticle) {
            return $this;
        }

        $linkedArticle->validate();
        $this->links[] = $linkedArticle;
        return $this;
    }

    /**
     * @param array $linkedArticles
     *
     * @return static
     */
    public function addLinks(?array $linkedArticles = []): self
    {
        foreach ($linkedArticles as $linkedArticle) {
            $this->addLink($linkedArticle);
        }

        return $this;
    }

    /**
     * @param array $links
     *
     * @return static
     */
    public function setLinks(?array $links = []): self
    {
        $this->links = [];
        $this->addLinks($links);
        return $this;
    }

    /**
     * @return string
     */
    public function getThumbnailURL(): ?string
    {
        return $this->thumbnailURL;
    }

    /**
     * @param string $thumbnailURL
     *
     * @return static
     */
    public function setThumbnailURL(?string $thumbnailURL = null): self
    {
        if (is_string($thumbnailURL)) {
            Assertion::url($thumbnailURL);
        }

        $this->thumbnailURL = $thumbnailURL;
        return $this;
    }

    /**
     * @return bool
     */
    public function getTransparentToolbar(): ?bool
    {
        return $this->transparentToolbar;
    }

    /**
     * @param bool $transparentToolbar
     *
     * @return static
     */
    public function setTransparentToolbar(?bool $transparentToolbar = true): self
    {
        $this->transparentToolbar = $transparentToolbar;
        return $this;
    }

    /**
     * @return string
     */
    public function getVideoURL(): ?string
    {
        return $this->videoURL;
    }

    /**
     * @param string $videoURL
     *
     * @return static
     */
    public function setVideoURL(?string $videoURL = null): self
    {
        if (is_string($videoURL)) {
            Assertion::url($videoURL);
        }

        $this->videoURL = $videoURL;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize(): array
    {
        return $this->getSetProperties();
    }
}
