<?php
declare(strict_types=1);

namespace Triniti\AppleNews\Component;

use Assert\Assertion;
use Triniti\AppleNews\CaptionDescriptor;

/**
 * @link: https://developer.apple.com/documentation/apple_news/portrait
 */
class Portrait extends Component
{
    /** @var string */
    public $URL; //we will not support bundles yet

    /** @var string */
    public $accessibilityCaption;

    /** @var string|CaptionDescriptor */
    public $caption;

    /** @var bool */
    public $explicitContent;

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
     * @return string|CaptionDescriptor
     */
    public function getCaption()
    {
        return $this->caption;
    }

    /**
     * @param string|CaptionDescriptor $caption
     *
     * @return static
     */
    public function setCaption($caption = null): self
    {
        if (!is_string($caption) && null !== $caption && !($caption instanceof CaptionDescriptor)) {
            Assertion::true(
                false,
                'Caption must be a string or instance of CaptionDescriptor.'
            );
        }

        if ($caption instanceof CaptionDescriptor) {
            $caption->validate();
        }

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
     * {@inheritdoc}
     */
    public function validate(): void
    {
        Assertion::notNull($this->URL);
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        $properties = $this->getSetProperties();
        $properties['role'] = 'portrait';
        return $properties;
    }
}
