<?php
declare(strict_types=1);

namespace Triniti\AppleNews\Style;

use Assert\Assertion;

/**
 * @link https://developer.apple.com/documentation/apple_news/video_fill
 */
class VideoFill extends Fill
{
    /** @var string */
    public $URL;

    /** @var string */
    public $fillMode;

    /** @var string */
    public $horizontalAlignment;

    /** @var bool */
    public $loop;

    /** @var string */
    public $stillURL;

    /** @var string */
    public $verticalAlignment;

    /**
     * @var string[] Valid fill mode values
     */
    private $validFillModes =
        [
            'fit',
            'cover',
        ];
    /**
     * @var string[] Valid horizontal alignment values
     */
    private $validHorizontalAlignments =
        [
            'left',
            'center',
            'right',
        ];
    /**
     * @var string[] Valid vertical alignment values
     */
    private $validVerticalAlignments =
        [
            'top',
            'center',
            'bottom',
        ];

    /**
     * @return string|null
     */
    public function getURL()
    {
        return $this->URL;
    }

    /**
     * @param string $URL
     *
     * @return VideoFill
     */
    public function setURL(string $URL): self
    {
        $this->URL = $URL;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getFillMode()
    {
        return $this->fillMode;
    }

    /**
     * @param null|string $fillMode
     *
     * @return VideoFill
     */
    public function setFillMode(?string $fillMode = 'cover'): self
    {
        Assertion::inArray($fillMode, $this->validFillModes);

        $this->fillMode = $fillMode;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getHorizontalAlignment()
    {
        return $this->horizontalAlignment;
    }

    /**
     * @param null|string $horizontalAlignment
     *
     * @return VideoFill
     */
    public function setHorizontalAlignment(?string $horizontalAlignment = 'center'): self
    {
        Assertion::inArray($horizontalAlignment, $this->validHorizontalAlignments);

        $this->horizontalAlignment = $horizontalAlignment;

        return $this;
    }

    /**
     * @return bool|null
     */
    public function getLoop(): bool
    {
        return $this->loop;
    }

    /**
     * @param bool|null $loop
     *
     * @return VideoFill
     */
    public function setLoop(?bool $loop = true): self
    {
        $this->loop = $loop;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getStillURL()
    {
        return $this->stillURL;
    }

    /**
     * @param string $stillURL
     *
     * @return VideoFill
     */
    public function setStillURL(string $stillURL): self
    {
        $this->stillURL = $stillURL;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getVerticalAlignment()
    {
        return $this->verticalAlignment;
    }

    /**
     * @param null|string $verticalAlignment
     *
     * @return VideoFill
     */
    public function setVerticalAlignment(?string $verticalAlignment = 'center'): self
    {
        Assertion::inArray($verticalAlignment, $this->validVerticalAlignments);

        $this->verticalAlignment = $verticalAlignment;

        return $this;
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        $properties = $this->getSetProperties();
        $properties['type'] = 'video';

        return $properties;
    }

    /**
     * @throws \Assert\AssertionFailedException
     */
    public function validate(): void
    {
        Assertion::notNull($this->URL);
        Assertion::notNull($this->stillURL);
    }
}
