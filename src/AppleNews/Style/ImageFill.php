<?php
declare(strict_types=1);

namespace Triniti\AppleNews\Style;

use Assert\Assertion;

/**
 * An Apple News ImageFill.
 */
class ImageFill extends Fill
{
    protected ?string $URL = null;
    protected ?string $fillMode = null;
    protected ?string $horizontalAlignment = null;
    protected ?string $verticalAlignment = null;

    /**
     * @var string[] Valid fill mode
     */
    private array $validFillModes = [
        'fit',
        'cover',
    ];

    /**
     * @var string[] Valid horizontal alignment
     */
    private array $validHorizontalAlignments = [
        'left',
        'center',
        'right',
    ];

    /**
     * @var string[] Valid vertical alignment
     */
    private array $validVerticalAlignments = [
        'top',
        'center',
        'bottom',
    ];

    public function getURL(): ?string
    {
        return $this->URL;
    }

    public function setURL(string $URL): self
    {
        $this->URL = $URL;
        return $this;
    }

    public function getFillMode(): ?string
    {
        return $this->fillMode;
    }

    public function setFillMode(?string $fillMode = 'cover'): self
    {
        if (is_string($fillMode)) {
            Assertion::inArray($fillMode, $this->validFillModes);
        }

        $this->fillMode = $fillMode;
        return $this;
    }

    public function getHorizontalAlignment(): ?string
    {
        return $this->horizontalAlignment;
    }

    public function setHorizontalAlignment(?string $horizontalAlignment = 'center'): self
    {
        if (is_string($horizontalAlignment)) {
            Assertion::inArray($horizontalAlignment, $this->validHorizontalAlignments);
        }

        $this->horizontalAlignment = $horizontalAlignment;
        return $this;
    }

    public function getVerticalAlignment(): ?string
    {
        return $this->verticalAlignment;
    }

    public function setVerticalAlignment(?string $verticalAlignment = 'center'): self
    {
        if (is_string($verticalAlignment)) {
            Assertion::inArray($verticalAlignment, $this->validVerticalAlignments);
        }

        $this->verticalAlignment = $verticalAlignment;
        return $this;
    }

    public function jsonSerialize()
    {
        $properties = $this->getSetProperties();
        $properties['type'] = 'image';
        return $properties;
    }

    public function validate(): void
    {
        Assertion::notNull($this->URL);
    }
}
