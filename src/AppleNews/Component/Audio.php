<?php
declare(strict_types=1);

namespace Triniti\AppleNews\Component;

use Assert\Assertion;

/**
 * @link https://developer.apple.com/documentation/apple_news/audio
 */
class Audio extends Component
{
    /** @var string */
    protected $URL;

    /** @var string */
    protected $accessibilityCaption;

    /** @var string */
    protected $caption;

    /** @var bool */
    protected $explicitContent;

    /** @var string */
    protected $imageURL; //we will not support bundles yet

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
     * @return string
     */
    public function getCaption(): ?string
    {
        return $this->caption;
    }

    /**
     * @param string $caption
     *
     * @return static
     */
    public function setCaption(?string $caption = null): self
    {
        $this->caption = $caption;
        return $this;
    }

    /**
     * @return bool
     */
    public function getExplicitContent(): ?bool
    {
        return $this->explicitContent;
    }

    /**
     * @param bool $explicitContent
     *
     * @return static
     */
    public function setExplicitContent(bool $explicitContent = false): self
    {
        $this->explicitContent = $explicitContent;
        return $this;
    }

    /**
     * @return string
     */
    public function getImageURL(): ?string
    {
        return $this->imageURL;
    }

    /**
     * @param string $imageUrl
     *
     * @return static
     */
    public function setImageURL(string $imageUrl): self
    {
        Assertion::url($imageUrl);
        $this->imageURL = $imageUrl;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function validate(): void
    {
        Assertion::notNull($this->URL);
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize(): array
    {
        $properties = $this->getSetProperties();
        $properties['role'] = 'audio';
        return $properties;
    }
}

