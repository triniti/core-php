<?php
declare(strict_types=1);

namespace Triniti\AppleNews\Style;

use Assert\Assertion;

/**
 * An Apple News ImageFill.
 */
class ImageFill extends Fill
{
    /** @var  string */
    protected $URL;

    /** @var  string */
    protected $fillMode;

    /** @var  string */
    protected $horizontalAlignment;

    /** @var  string */
    protected $verticalAlignment;

    /**
     * @var string[] Valid fill mode
     */
    private $validFillModes = [
        'fit',
        'cover',
    ];

    /**
     * @var string[] Valid horizontal alignment
     */
    private $validHorizontalAlignments = [
        'left',
        'center',
        'right',
    ];

    /**
     * @var string[] Valid vertical alignment
     */
    private $validVerticalAlignments = [
        'top',
        'center',
        'bottom',
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
        $this->URL = $URL;
        return $this;
    }

    /**
     * @return string
     */
    public function getFillMode(): ?string
    {
        return $this->fillMode;
    }

    /**
     * @param string $fillMode
     *
     * @return static
     */
    public function setFillMode(?string $fillMode = 'cover'): self
    {
        if (is_string($fillMode)) {
            Assertion::inArray($fillMode, $this->validFillModes);
        }

        $this->fillMode = $fillMode;
        return $this;
    }

    /**
     * @return string
     */
    public function getHorizontalAlignment(): ?string
    {
        return $this->horizontalAlignment;
    }

    /**
     * @param string $horizontalAlignment
     *
     * @return static
     */
    public function setHorizontalAlignment(?string $horizontalAlignment = 'center'): self
    {
        if (is_string($horizontalAlignment)) {
            Assertion::inArray($horizontalAlignment, $this->validHorizontalAlignments);
        }

        $this->horizontalAlignment = $horizontalAlignment;
        return $this;
    }

    /**
     * @return string
     */
    public function getVerticalAlignment(): ?string
    {
        return $this->verticalAlignment;
    }

    /**
     * @param string $verticalAlignment
     *
     * @return static
     */
    public function setVerticalAlignment(?string $verticalAlignment = 'center'): self
    {
        if (is_string($verticalAlignment)) {
            Assertion::inArray($verticalAlignment, $this->validVerticalAlignments);
        }

        $this->verticalAlignment = $verticalAlignment;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize(): mixed
    {
        $properties = $this->getSetProperties();
        $properties['type'] = 'image';
        return $properties;
    }

    /**
     * {@inheritdoc}
     */
    public function validate(): void
    {
        Assertion::notNull($this->URL);
    }
}
