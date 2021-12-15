<?php
declare(strict_types=1);

namespace Triniti\AppleNews;

use Assert\Assertion;

/**
 * @link https://developer.apple.com/documentation/apple_news/cover_art
 */
class CoverArt extends AppleNewsObject
{
    /** @var string */
    protected $URL;

    /** @var string */
    protected $accessibilityCaption;

    /**
     * @return string
     */
    public function getURL(): ?string
    {
        return $this->URL;
    }

    /**
     * @param string $url
     *
     * @return static
     */
    public function setURL(string $url): self
    {
        Assertion::url($url);
        $this->URL = $url;
        return $this;
    }

    /**
     * @return string
     */
    public function getAccessibilityCaption(): ?string
    {
        return $this->accessibilityCaption;
    }

    /**
     * @param string $accessibilityCaption
     *
     * @return static
     */
    public function setAccessibilityCaption(?string $accessibilityCaption = null): self
    {
        $this->accessibilityCaption = $accessibilityCaption;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize(): mixed
    {
        $properties = $this->getSetProperties();
        $properties['type'] = 'image';
        return $properties;
    }

    /**
     * {@inheritdoc}
     */
    public function validate(): void
    {
        Assertion::notNull($this->URL);
    }
}
