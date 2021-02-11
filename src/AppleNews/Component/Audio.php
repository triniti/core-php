<?php
declare(strict_types=1);

namespace Triniti\AppleNews\Component;

use Assert\Assertion;

/**
 * @link https://developer.apple.com/documentation/apple_news/audio
 */
class Audio extends Component
{
    protected ?string $URL = null;
    protected ?string $accessibilityCaption = null;
    protected ?string $caption = null;
    protected bool $explicitContent;
    protected string $imageURL; //we will not support bundles yet

    public function getURL(): ?string
    {
        return $this->URL;
    }

    public function setURL(string $url): self
    {
        Assertion::url($url);
        $this->URL = $url;
        return $this;
    }

    public function getAccessibilityCaption(): ?string
    {
        return $this->accessibilityCaption;
    }

    public function setAccessibilityCaption(?string $accessibilityCaption = null): self
    {
        $this->accessibilityCaption = $accessibilityCaption;
        return $this;
    }

    public function getCaption(): ?string
    {
        return $this->caption;
    }

    public function setCaption(?string $caption = null): self
    {
        $this->caption = $caption;
        return $this;
    }

    public function getExplicitContent(): ?bool
    {
        return $this->explicitContent;
    }

    public function setExplicitContent(bool $explicitContent = false): self
    {
        $this->explicitContent = $explicitContent;
        return $this;
    }

    public function getImageURL(): ?string
    {
        return $this->imageURL;
    }

    public function setImageURL(string $imageUrl): self
    {
        Assertion::url($imageUrl);
        $this->imageURL = $imageUrl;
        return $this;
    }

    public function validate(): void
    {
        Assertion::notNull($this->URL);
    }

    public function jsonSerialize()
    {
        $properties = $this->getSetProperties();
        $properties['role'] = 'audio';
        return $properties;
    }
}

