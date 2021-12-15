<?php
declare(strict_types=1);

namespace Triniti\AppleNews;

use Assert\Assertion;

/**
 * https://developer.apple.com/documentation/apple_news/linked_article
 */
class LinkedArticle extends AppleNewsObject
{
    /** @var string */
    protected $URL;

    /** @var string */
    protected $relationship;

    /** @var string[] */
    private $validRelationships = [
        'related',
        'promoted',
    ];

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
        Assertion::url($URL);
        $this->URL = $URL;
        return $this;
    }

    /**
     * @return string
     */
    public function getRelationship(): ?string
    {
        return $this->relationship;
    }

    /**
     * @param string $relationship
     *
     * @return static
     */
    public function setRelationship(string $relationship): self
    {
        Assertion::inArray($relationship, $this->validRelationships);
        $this->relationship = $relationship;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize(): array
    {
        return $this->getSetProperties();
    }

    /**
     * {@inheritdoc}
     */
    public function validate(): void
    {
        Assertion::notNull($this->URL);
        Assertion::notNull($this->relationship);
    }
}
