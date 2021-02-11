<?php
declare(strict_types=1);

namespace Triniti\AppleNews\Component;

use Assert\Assertion;

/**
 * @link https://developer.apple.com/documentation/apple_news/instagram
 */
class Instagram extends Component
{
    protected ?string $URL = null;

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

    public function validate(): void
    {
        Assertion::notNull($this->URL);
    }

    public function jsonSerialize()
    {
        $properties = $this->getSetProperties();
        $properties['role'] = 'instagram';
        return $properties;
    }
}


