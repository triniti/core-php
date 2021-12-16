<?php
declare(strict_types=1);

namespace Triniti\AppleNews\Link;

use Assert\Assertion;

/**
 * @link https://developer.apple.com/documentation/apple_news/componentlink
 */
class ComponentLink extends ComponentAddition
{
    /** @var string */
    protected $URL;

    /**
     * @return string
     */
    public function getURL(): ?string
    {
        return $this->URL;
    }

    /**
     * @param string $URL
     *
     * @return static
     */
    public function setURL(string $URL): self
    {
        Assertion::url($URL, 'Invalid URL');
        $this->URL = $URL;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize(): array
    {
        $properties = $this->getSetProperties();
        $properties['type'] = 'link';
        return $properties;
    }

    /**
     * {@inheritdoc}
     */
    public function validate(): void
    {
        Assertion::notNull($this->URL, 'URL is required');
    }
}
