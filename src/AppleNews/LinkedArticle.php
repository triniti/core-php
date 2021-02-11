<?php
declare(strict_types=1);

namespace Triniti\AppleNews;

use Assert\Assertion;

/**
 * https://developer.apple.com/documentation/apple_news/linked_article
 */
class LinkedArticle extends AppleNewsObject
{
    protected ?string $URL = null;
    protected ?string $relationship = null;

    /** @var string[] */
    private array $validRelationships = [
        'related',
        'promoted',
    ];

    public function getURL(): ?string
    {
        return $this->URL;
    }

    public function setURL(string $URL): self
    {
        Assertion::url($URL);
        $this->URL = $URL;
        return $this;
    }

    public function getRelationship(): ?string
    {
        return $this->relationship;
    }

    public function setRelationship(string $relationship): self
    {
        Assertion::inArray($relationship, $this->validRelationships);
        $this->relationship = $relationship;
        return $this;
    }

    public function jsonSerialize()
    {
        return $this->getSetProperties();
    }

    public function validate(): void
    {
        Assertion::notNull($this->URL);
        Assertion::notNull($this->relationship);
    }
}
