<?php
declare(strict_types=1);

namespace Triniti\AppleNews\Component;

use Assert\Assertion;

/**
 * @link https://developer.apple.com/documentation/apple_news/embed_web_video
 */
class EmbedWebVideo extends Component
{
    /** @var string */
    public $role;

    /** @var string */
    public $URL;

    /** @var string */
    public $accessibilityCaption;

    /** @var float */
    public $aspectRatio;

    /** @var string */
    public $caption;

    /** @var bool */
    public $explicitContent;

    /**
     * @return string
     */
    public function getRole(): ?string
    {
        return $this->role;
    }

    /**
     * @param string $role
     *
     * @return static
     */
    public function setRole(string $role): self
    {
        Assertion::inArray(
            $role,
            ['embedwebvideo', 'embedvideo'],
            'Role must be one of the following values: embedwebvideo, embedvideo.'
        );

        $this->role = $role;
        return $this;
    }

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
     * @return float
     */
    public function getAspectRatio(): ?float
    {
        return $this->aspectRatio;
    }

    /**
     * @param float $aspectRatio
     *
     * @return static
     */
    public function setAspectRatio(float $aspectRatio = 1.777): self
    {
        $this->aspectRatio = $aspectRatio;
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
     * {@inheritdoc}
     */
    public function validate(): void
    {
        Assertion::notNull($this->role);
        Assertion::notNull($this->URL);
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize(): array
    {
        $properties = $this->getSetProperties();
        $properties['role'] = $this->role;
        return $properties;
    }
}
