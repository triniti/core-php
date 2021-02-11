<?php
declare(strict_types=1);

namespace Triniti\AppleNews;

use Assert\Assertion;

/**
 * @link https://developer.apple.com/documentation/apple_news/metadata
 */
class Metadata extends AppleNewsObject
{
    protected ?string $canonicalURL = null;
    protected ?string $dateCreated = null;
    protected ?string $dateModified = null;
    protected ?string $datePublished = null;
    protected ?string $excerpt = null;
    protected ?string $thumbnailURL = null;
    protected ?string $videoURL = null;
    protected array $campaignData = [];
    protected bool $transparentToolbar;
    protected string $generatorIdentifier = 'TrinitiAppleNewsPlugin';
    protected string $generatorName = 'Triniti Apple News Plugin';
    protected string $generatorVersion = '0.1';

    /** @var CoverArt[] */
    protected array $coverArt = [];

    /** @var string[] */
    protected array $authors = [];

    /** @var string[] */
    protected array $keywords = [];

    /** @var LinkedArticle[] */
    protected array $links = [];

    /**
     * @return string[]
     */
    public function getAuthors(): array
    {
        return $this->authors;
    }

    public function setAuthors(array $authors = []): self
    {
        $this->authors = $authors;
        return $this;
    }

    public function getCampaignData(): array
    {
        return $this->campaignData;
    }

    public function setCampaignData(array $campaignData = []): self
    {
        $this->campaignData = $campaignData;
        return $this;
    }

    public function getCanonicalURL(): ?string
    {
        return $this->canonicalURL;
    }

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

    public function getDateCreated(): ?string
    {
        return $this->dateCreated;
    }

    public function setDateCreated(?string $dateCreated = null): self
    {
        $this->dateCreated = $dateCreated;
        return $this;
    }

    public function getDateModified(): ?string
    {
        return $this->dateModified;
    }

    public function setDateModified(?string $dateModified = null): self
    {
        $this->dateModified = $dateModified;
        return $this;
    }

    public function getDatePublished(): ?string
    {
        return $this->datePublished;
    }

    public function setDatePublished(?string $datePublished = null): self
    {
        $this->datePublished = $datePublished;
        return $this;
    }

    public function getExcerpt(): ?string
    {
        return $this->excerpt;
    }

    public function setExcerpt(?string $excerpt = null): self
    {
        $this->excerpt = $excerpt;
        return $this;
    }

    public function getGeneratorIdentifier(): string
    {
        return $this->generatorIdentifier;
    }

    public function setGeneratorIdentifier(string $generatorIdentifier): self
    {
        $this->generatorIdentifier = $generatorIdentifier;
        return $this;
    }

    public function getGeneratorName(): string
    {
        return $this->generatorName;
    }

    public function setGeneratorName(string $generatorName): self
    {
        $this->generatorName = $generatorName;
        return $this;
    }

    public function getGeneratorVersion(): string
    {
        return $this->generatorVersion;
    }

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

    public function addLinks(?array $linkedArticles = []): self
    {
        foreach ($linkedArticles as $linkedArticle) {
            $this->addLink($linkedArticle);
        }

        return $this;
    }

    public function setLinks(?array $links = []): self
    {
        $this->links = [];
        $this->addLinks($links);
        return $this;
    }

    public function getThumbnailURL(): ?string
    {
        return $this->thumbnailURL;
    }

    public function setThumbnailURL(?string $thumbnailURL = null): self
    {
        if (is_string($thumbnailURL)) {
            Assertion::url($thumbnailURL);
        }

        $this->thumbnailURL = $thumbnailURL;
        return $this;
    }

    public function getTransparentToolbar(): ?bool
    {
        return $this->transparentToolbar;
    }

    public function setTransparentToolbar(?bool $transparentToolbar = true): self
    {
        $this->transparentToolbar = $transparentToolbar;
        return $this;
    }

    public function getVideoURL(): ?string
    {
        return $this->videoURL;
    }

    public function setVideoURL(?string $videoURL = null): self
    {
        if (is_string($videoURL)) {
            Assertion::url($videoURL);
        }

        $this->videoURL = $videoURL;
        return $this;
    }

    public function jsonSerialize()
    {
        return $this->getSetProperties();
    }
}
