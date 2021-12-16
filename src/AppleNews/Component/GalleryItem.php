<?php
declare(strict_types=1);

namespace Triniti\AppleNews\Component;

use Assert\Assertion;
use Triniti\AppleNews\CaptionDescriptor;

/**
 * @link https://developer.apple.com/documentation/apple_news/gallery_item
 */
class GalleryItem extends Component
{
    /** @var string */
    protected $URL; //we will not support bundles yet

    /** @var string|CaptionDescriptor */
    protected $caption;

    /** @var string */
    protected $accessibilityCaption;

    /** @var bool */
    protected $explicitContent;

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
        if (!is_string($caption) && (null !== $caption) && !($caption instanceof CaptionDescriptor)) {
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
     * @return GalleryItem
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
    public function jsonSerialize(): array
    {
        return $this->getSetProperties();
    }
}
