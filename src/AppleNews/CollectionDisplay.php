<?php
declare(strict_types=1);

namespace Triniti\AppleNews;

use Assert\Assertion;

/**
 * @link https://developer.apple.com/documentation/apple_news/collection_display
 */
class CollectionDisplay extends AppleNewsObject
{
    protected string $alignment;
    protected string $distribution;
    protected bool $variableSizing;
    protected string $widows;

    /** @var int|SupportedUnits */
    protected $gutter;

    /** @var int|SupportedUnits */
    protected $maximumWidth;

    /** @var int|SupportedUnits */
    protected $minimumWidth;

    /** @var int|SupportedUnits */
    protected $rowSpacing;

    public function getAlignment(): ?string
    {
        return $this->alignment;
    }

    public function setAlignment(?string $alignment = 'left'): self
    {
        if (null === $alignment) {
            $alignment = 'left';
        }

        Assertion::inArray($alignment, ['left', 'center', 'right'], 'Alignment must be one of the following values: left, center, right.');
        $this->alignment = $alignment;
        return $this;
    }

    public function getDistribution(): ?string
    {
        return $this->distribution;
    }

    function setDistribution(?string $distribution = 'wide'): self
    {
        if (null === $distribution) {
            $distribution = 'wide';
        }

        Assertion::inArray($distribution, ['wide', 'narrow'], 'Distribution must be one of the following values: wide, narrow.');
        $this->distribution = $distribution;
        return $this;
    }

    /**
     * @return int|SupportedUnits
     */
    public function getGutter()
    {
        return $this->gutter;
    }

    /**
     * @param int|SupportedUnits $gutter
     *
     * @return static
     */
    public function setGutter($gutter = null): self
    {
        if (!is_int($gutter) && (null !== $gutter) && !($gutter instanceof SupportedUnits)) {
            Assertion::true(
                false,
                'Gutter must be an int or instance of SupportedUnits.'
            );
        }

        if ($gutter instanceof SupportedUnits) {
            $gutter->validate();
        }

        $this->gutter = $gutter;
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
                'Minimum width must be an int or instance of SupportedUnits.'
            );
        }

        if ($minimumWidth instanceof SupportedUnits) {
            $minimumWidth->validate();
        }

        $this->minimumWidth = $minimumWidth;
        return $this;
    }

    /**
     * @return int|SupportedUnits
     */
    public function getRowSpacing()
    {
        return $this->rowSpacing;
    }

    /**
     * @param int|SupportedUnits $rowSpacing
     *
     * @return static
     */
    public function setRowSpacing($rowSpacing = null): self
    {
        if (!is_int($rowSpacing) && (null !== $rowSpacing) && !($rowSpacing instanceof SupportedUnits)) {
            Assertion::true(
                false,
                'Row spacing must be an int or instance of SupportedUnits.'
            );
        }

        if ($rowSpacing instanceof SupportedUnits) {
            $rowSpacing->validate();
        }

        $this->rowSpacing = $rowSpacing;
        return $this;
    }

    public function getVariableSizing(): ?bool
    {
        return $this->variableSizing;
    }

    public function setVariableSizing(?bool $variableSizing = false): self
    {
        if (null === $variableSizing) {
            $variableSizing = false;
        }

        $this->variableSizing = $variableSizing;
        return $this;
    }

    public function getWidows(): ?string
    {
        return $this->widows;
    }

    public function setWidows(?string $widows = 'optimize'): self
    {
        if (null === $widows) {
            $widows = 'optimize';
        }

        Assertion::inArray($widows, ['equalize', 'optimize'], 'Widows must be one of the following values: equalize, optimize.');
        $this->widows = $widows;
        return $this;
    }

    public function jsonSerialize()
    {
        $properties = $this->getSetProperties();
        $properties['type'] = 'collection';
        return $properties;
    }
}

