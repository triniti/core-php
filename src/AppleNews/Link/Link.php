<?php
declare(strict_types=1);

namespace Triniti\AppleNews\Link;

use Assert\Assertion;

/**
 * @link https://developer.apple.com/documentation/apple_news/linkaddition
 */
class Link extends Addition
{
    protected ?string $URL = null;

    public function getURL(): ?string
    {
        return $this->URL;
    }

    public function setURL(string $URL): self
    {
        Assertion::url($URL, 'Invalid URL');
        $this->URL = $URL;
        return $this;
    }

    public function jsonSerialize()
    {
        $properties = $this->getSetProperties();
        $properties['type'] = 'link';
        return $properties;
    }

    public function validate(): void
    {
        Assertion::notNull($this->URL, 'URL is required');
        parent::validate();
    }
}
