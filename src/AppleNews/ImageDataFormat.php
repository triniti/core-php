<?php
declare(strict_types=1);

namespace Triniti\AppleNews;

use Assert\Assertion;

/**
 * @link https://developer.apple.com/documentation/apple_news/image_data_format
 */
class ImageDataFormat extends DataFormat
{
    /** @var int|SupportedUnits */
    protected $maximumHeight;

    /** @var int|SupportedUnits */
    protected $maximumWidth;

    /** @var int|SupportedUnits */
    protected $minimumHeight;

    /** @var int|SupportedUnits */
    protected $minimumWidth;

    /**
     * @return int|SupportedUnits
     */
    public function getMaximumHeight()
    {
        return $this->maximumHeight;
    }

    /**
     * @param int|SupportedUnits $maximumHeight
     *
     * @return static
     */
    public function setMaximumHeight($maximumHeight = null): self
    {
        if (!is_int($maximumHeight) && (null !== $maximumHeight) && !($maximumHeight instanceof SupportedUnits)) {
            Assertion::true(
                false,
                'Maximum height must be an int or instance of SupportedUnits.'
            );
        }

        if ($maximumHeight instanceof SupportedUnits) {
            $maximumHeight->validate();
        }

        $this->maximumHeight = $maximumHeight;
        return $this;
    }

    /**
     * @return int|SupportedUnits
     */
    public function getMaximumWidth()
    {
        return $this->maximumWidth;
    }

    /**
     * @param int|SupportedUnits $maximumWidth
     *
     * @return static
     */
    public function setMaximumWidth($maximumWidth = null): self
    {
        if (!is_int($maximumWidth) && (null !== $maximumWidth) && !($maximumWidth instanceof SupportedUnits)) {
            Assertion::true(
                false,
                'Maximum width must be an int or instance of SupportedUnits.'
            );
        }

        if ($maximumWidth instanceof SupportedUnits) {
            $maximumWidth->validate();
        }

        $this->maximumWidth = $maximumWidth;
        return $this;
    }

    /**
     * @return int|SupportedUnits
     */
    public function getMinimumHeight()
    {
        return $this->minimumHeight;
    }

    /**
     * @param int|SupportedUnits $minimumHeight
     *
     * @return static
     */
    public function setMinimumHeight($minimumHeight = null): self
    {
        if (!is_int($minimumHeight) && (null !== $minimumHeight) && !($minimumHeight instanceof SupportedUnits)) {
            Assertion::true(
                false,
                'Minimum height must be an int or instance of SupportedUnits.'
            );
        }

        if ($minimumHeight instanceof SupportedUnits) {
            $minimumHeight->validate();
        }

        $this->minimumHeight = $minimumHeight;
        return $this;
    }

    /**
     * @return int|SupportedUnits
     */
    public function getMinimumWidth()
    {
        return $this->minimumWidth;
    }

    /**
     * @param int|SupportedUnits $minimumWidth
     *
     * @return static
     */
    public function setMinimumWidth($minimumWidth = null): self
    {
        if (!is_int($minimumWidth) && (null !== $minimumWidth) && !($minimumWidth instanceof SupportedUnits)) {
            Assertion::true(
                false,
                'Minimum height must be an int or instance of SupportedUnits.'
            );
        }

        if ($minimumWidth instanceof SupportedUnits) {
            $minimumWidth->validate();
        }

        $this->minimumWidth = $minimumWidth;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        $properties = $this->getSetProperties();
        $properties['type'] = 'image';
        return $properties;
    }
}
