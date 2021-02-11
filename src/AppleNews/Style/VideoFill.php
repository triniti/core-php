<?php
declare(strict_types=1);

namespace Triniti\AppleNews\Style;

use Assert\Assertion;

/**
 * @link https://developer.apple.com/documentation/apple_news/video_fill
 */
class VideoFill extends Fill
{
    public ?string $URL = null;
    public ?string $fillMode = null;
    public ?string $horizontalAlignment = null;
    public ?bool $loop = null;
    public ?string $stillURL = null;
    public ?string $verticalAlignment = null;

    /**
     * @var string[] Valid fill mode values
     */
    private array $validFillModes =
        [
            'fit',
            'cover',
        ];

    /**
     * @var string[] Valid horizontal alignment values
     */
    private array $validHorizontalAlignments =
        [
            'left',
            'center',
            'right',
        ];

    /**
     * @var string[] Valid vertical alignment values
     */
    private array $validVerticalAlignments =
        [
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
        Assertion::inArray($fillMode, $this->validFillModes);
        $this->fillMode = $fillMode;
        return $this;
    }

    public function getHorizontalAlignment(): ?string
    {
        return $this->horizontalAlignment;
    }

    public function setHorizontalAlignment(?string $horizontalAlignment = 'center'): self
    {
        Assertion::inArray($horizontalAlignment, $this->validHorizontalAlignments);
        $this->horizontalAlignment = $horizontalAlignment;
        return $this;
    }

    public function getLoop(): ?bool
    {
        return $this->loop;
    }

    public function setLoop(?bool $loop = true): self
    {
        $this->loop = $loop;

        return $this;
    }

    public function getStillURL(): ?string
    {
        return $this->stillURL;
    }

    public function setStillURL(string $stillURL): self
    {
        $this->stillURL = $stillURL;
        return $this;
    }

    public function getVerticalAlignment(): ?string
    {
        return $this->verticalAlignment;
    }

    public function setVerticalAlignment(?string $verticalAlignment = 'center'): self
    {
        Assertion::inArray($verticalAlignment, $this->validVerticalAlignments);
        $this->verticalAlignment = $verticalAlignment;
        return $this;
    }

    public function jsonSerialize(): array
    {
        $properties = $this->getSetProperties();
        $properties['type'] = 'video';

        return $properties;
    }

    public function validate(): void
    {
        Assertion::notNull($this->URL);
        Assertion::notNull($this->stillURL);
    }
}
