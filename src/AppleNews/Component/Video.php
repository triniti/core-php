<?php
declare(strict_types=1);

namespace Triniti\AppleNews\Component;

use Assert\Assertion;

/**
 * @link https://developer.apple.com/documentation/apple_news/video
 */
class Video extends Component
{
    public ?string $URL = null;
    public ?string $accessibilityCaption = null;
    public float $aspectRatio;
    public ?string $caption = null;
    public bool $explicitContent;
    public string $stillURL; //we will not support bundles yet

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

    public function getAspectRatio(): ?float
    {
        return $this->aspectRatio;
    }

    public function setAspectRatio(float $aspectRatio = 1.777): self
    {
        $this->aspectRatio = $aspectRatio;
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

    public function getStillURL(): ?string
    {
        return $this->stillURL;
    }

    public function setStillURL(string $stillUrl): self
    {
        Assertion::url($stillUrl);
        $this->stillURL = $stillUrl;
        return $this;
    }

    public function validate(): void
    {
        Assertion::notNull($this->URL);
    }

    public function jsonSerialize()
    {
        $properties = $this->getSetProperties();
        $properties['role'] = 'video';
        return $properties;
    }
}
