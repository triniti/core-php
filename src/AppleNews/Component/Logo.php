<?php
declare(strict_types=1);

namespace Triniti\AppleNews\Component;

use Assert\Assertion;
use Triniti\AppleNews\Link\ComponentLink;

/**
 * @link https://developer.apple.com/documentation/apple_news/logo
 */
class Logo extends Component
{
    public ?string $URL = null; //we will not support bundles yet
    public ?string $accessibilityCaption = null;
    public ?string $caption = null;
    public bool $explicitContent;

    /** @var ComponentLink[] */
    protected array $additions = [];

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

    /**
     * @return ComponentLink[]
     */
    public function getAdditions(): array
    {
        return $this->additions;
    }

    /**
     * @param ComponentLink[] $additions
     *
     * @return static
     */
    public function setAdditions(?array $additions = []): self
    {
        $this->additions = [];

        if (null !== $additions) {
            foreach ($additions as $addition) {
                $this->addAddition($addition);
            }
        }

        return $this;
    }

    public function addAddition(?ComponentLink $addition = null): self
    {
        if (null !== $addition) {
            $addition->validate();
            $this->additions[] = $addition;
        }

        return $this;
    }

    /**
     * @param ComponentLink[] $additions
     *
     * @return static
     */
    public function addAdditions(?array $additions = []): self
    {
        if (null !== $additions) {
            foreach ($additions as $addition) {
                $this->addAddition($addition);
            }
        }

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

    public function validate(): void
    {
        Assertion::notNull($this->URL);
    }

    public function jsonSerialize()
    {
        $properties = $this->getSetProperties();
        $properties['role'] = 'logo';
        return $properties;
    }
}
