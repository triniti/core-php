<?php

declare(strict_types=1);

namespace Triniti\AppleNews;

use Assert\Assertion;

/**
 * @link https://developer.apple.com/documentation/apple_news/condition
 */
class Condition extends AppleNewsObject
{
    /** @var string */
    protected $horizontalSizeClass;

    /** @var int */
    protected $maxColumns;

    /** @var string */
    protected $maxContentSizeCategory;

    /** @var string */
    protected $maxSpecVersion;

    /** @var float */
    protected $maxViewportAspectRatio;

    /** @var int */
    protected $maxViewportWidth;

    /** @var int */
    protected $minColumns;

    /** @var string */
    protected $minContentSizeCategory;

    /** @var string */
    protected $minSpecVersion;

    /** @var float */
    protected $minViewportAspectRatio;

    /** @var int */
    protected $minViewportWidth;

    /** @var string */
    protected $platform;

    /** @var string */
    protected $preferredColorScheme;

    /** @var string */
    protected $subscriptionStatus;

    /** @var string */
    protected $verticalSizeClass;

    /** @var string */
    protected $viewLocation;

    /**
     * @var string[]
     */
    private $validHorizontalSizeClass = [
        'any',
        'regular',
        'compact',
    ];

    /**
     * @var string[]
     */
    private $validContentSizeCategory = [
        'XS',
        'S',
        'M',
        'L',
        'XL',
        'XXL',
        'XXXL',
        'AX-M',
        'AX-L',
        'AX-XL',
        'AX-XXL',
        'AX-XXXl',
    ];

    /**
     * @var string[]
     */
    private $validPlatform = [
        'any',
        'ios',
        'macos',
        'web',
    ];

    /**
     * @var string[]
     */
    private $validPreferredColorScheme = [
        'any',
        'light',
        'dark',
    ];

    /**
     * @var string[]
     */
    private $validSubscriptionStatus = [
        'bundle',
        'subscribed',
        'bundle_trial_eligible',
    ];

    /**
     * @var string[]
     */
    private $validVerticalSizeClass = [
        'any',
        'regular',
        'compact',
    ];

    /**
     * @var string[]
     */
    private $validViewLocation = [
        'any',
        'article',
        'issue_table_of_contents',
        'issue',
    ];

    /**
     * @return string
     */
    public function getHorizontalSizeClass(): ?string
    {
        return $this->horizontalSizeClass;
    }

    /**
     * @param string $horizontalSizeClass
     *
     * @return static
     */
    public function setHorizontalSizeClass(?string $horizontalSizeClass = null): self
    {
        if (null !== $horizontalSizeClass) {
            Assertion::inArray($horizontalSizeClass, $this->validHorizontalSizeClass);
        }

        $this->horizontalSizeClass = $horizontalSizeClass;
        return $this;
    }

    /**
     * @return int
     */
    public function getMaxColumns(): ?int
    {
        return $this->maxColumns;
    }

    /**
     * @param int $maxColumns
     *
     * @return static
     */
    public function setMaxColumns(?int $maxColumns = null): self
    {
        if (null !== $maxColumns) {
            if ($maxColumns < 1) {
                Assertion::true(
                    false,
                    'maxColumns must be greater than or equal to one.'
                );
            }
        }

        $this->maxColumns = $maxColumns;
        return $this;
    }

    /**
     * @return string
     */
    public function getMaxContentSizeCategory(): ?string
    {
        return $this->maxContentSizeCategory;
    }

    /**
     * @param string $maxContentSizeCategory
     *
     * @return static
     */
    public function setMaxContentSizeCategory(?string $maxContentSizeCategory = null): self
    {
        if (null !== $maxContentSizeCategory) {
            Assertion::inArray($maxContentSizeCategory, $this->validContentSizeCategory);
        }

        $this->maxContentSizeCategory = $maxContentSizeCategory;
        return $this;
    }

    /**
     * @return string
     */
    public function getMaxSpecVersion(): ?string
    {
        return $this->maxSpecVersion;
    }

    /**
     * @param string $maxSpecVersion
     *
     * @return static
     */
    public function setMaxSpecVersion(?string $maxSpecVersion = null): self
    {
        $this->maxSpecVersion = $maxSpecVersion;
        return $this;
    }

    /**
     * @return float
     */
    public function getMaxViewportAspectRatio(): ?float
    {
        return $this->maxViewportAspectRatio;
    }

    /**
     * @param float $maxViewportAspectRatio
     *
     * @return static
     */
    public function setMaxViewportAspectRatio(?float $maxViewportAspectRatio = null): self
    {
        if (null !== $maxViewportAspectRatio) {
            if ($maxViewportAspectRatio < 0) {
                Assertion::true(
                    false,
                    'maxViewportAspectRatio must be greater than or equal to zero.'
                );
            }
        }

        $this->maxViewportAspectRatio = $maxViewportAspectRatio;
        return $this;
    }

    /**
     * @return int
     */
    public function getMaxViewportWidth(): ?int
    {
        return $this->maxViewportWidth;
    }

    /**
     * @param int $maxViewportWidth
     *
     * @return static
     */
    public function setMaxViewportWidth(?int $maxViewportWidth = null): self
    {
        if (null !== $maxViewportWidth) {
            if ($maxViewportWidth < 0) {
                Assertion::true(
                    false,
                    'maxViewportWidth must be greater than or equal to zero.'
                );
            }
        }

        $this->maxViewportWidth = $maxViewportWidth;
        return $this;
    }

    /**
     * @return int
     */
    public function getMinColumns(): ?int
    {
        return $this->minColumns;
    }

    /**
     * @param int $minColumns
     *
     * @return static
     */
    public function setMinColumns(?int $minColumns = null): self
    {
        if (null !== $minColumns) {
            if ($minColumns < 0) {
                Assertion::true(
                    false,
                    'minColumns must be greater than or equal to zero.'
                );
            }
        }

        $this->minColumns = $minColumns;
        return $this;
    }

    /**
     * @return string
     */
    public function getMinContentSizeCategory(): ?string
    {
        return $this->minContentSizeCategory;
    }

    /**
     * @param string $minContentSizeCategory
     *
     * @return static
     */
    public function setMinContentSizeCategory(?string $minContentSizeCategory = null): self
    {
        if (null !== $minContentSizeCategory) {
            Assertion::inArray($minContentSizeCategory, $this->validContentSizeCategory);
        }

        $this->minContentSizeCategory = $minContentSizeCategory;
        return $this;
    }

    /**
     * @return string
     */
    public function getMinSpecVersion(): ?string
    {
        return $this->minSpecVersion;
    }

    /**
     * @param string $minSpecVersion
     *
     * @return static
     */
    public function setMinSpecVersion(?string $minSpecVersion = null): self
    {
        $this->minSpecVersion = $minSpecVersion;
        return $this;
    }

    /**
     * @return float
     */
    public function getMinViewportAspectRatio(): ?float
    {
        return $this->minViewportAspectRatio;
    }

    /**
     * @param float $minViewportAspectRatio
     *
     * @return static
     */
    public function setMinViewportAspectRatio(?float $minViewportAspectRatio = null): self
    {
        if (null !== $minViewportAspectRatio) {
            if ($minViewportAspectRatio < 0) {
                Assertion::true(
                    false,
                    'minViewportAspectRatio must be greater than or equal to zero.'
                );
            }
        }

        $this->minViewportAspectRatio = $minViewportAspectRatio;
        return $this;
    }

    /**
     * @return int
     */
    public function getMinViewportWidth(): ?int
    {
        return $this->minViewportWidth;
    }

    /**
     * @param int $minViewportWidth
     *
     * @return static
     */
    public function setMinViewportWidth(?int $minViewportWidth = null): self
    {
        if (null !== $minViewportWidth) {
            if ($minViewportWidth < 0) {
                Assertion::true(
                    false,
                    'minViewportWidth must be greater than or equal to zero.'
                );
            }
        }

        $this->minViewportWidth = $minViewportWidth;
        return $this;
    }

    /**
     * @return string
     */
    public function getPlatform(): ?string
    {
        return $this->platform;
    }

    /**
     * @param string $platform
     *
     * @return static
     */
    public function setPlatform(?string $platform = null): self
    {
        if (null !== $platform) {
            Assertion::inArray($platform, $this->validPlatform);
        }

        $this->platform = $platform;
        return $this;
    }

    /**
     * @return string
     */
    public function getPreferredColorScheme(): ?string
    {
        return $this->preferredColorScheme;
    }

    /**
     * @param string $preferredColorScheme
     *
     * @return static
     */
    public function setPreferredColorScheme(?string $preferredColorScheme = null): self
    {
        if (null !== $preferredColorScheme) {
            Assertion::inArray($preferredColorScheme, $this->validPreferredColorScheme);
        }

        $this->preferredColorScheme = $preferredColorScheme;
        return $this;
    }

    /**
     * @return string
     */
    public function getSubscriptionStatus(): ?string
    {
        return $this->subscriptionStatus;
    }

    /**
     * @param string $subscriptionStatus
     *
     * @return static
     */
    public function setSubscriptionStatus(?string $subscriptionStatus = null): self
    {
        if (null !== $subscriptionStatus) {
            Assertion::inArray($subscriptionStatus, $this->validSubscriptionStatus);
        }

        $this->subscriptionStatus = $subscriptionStatus;
        return $this;
    }

    /**
     * @return string
     */
    public function getVerticalSizeClass(): ?string
    {
        return $this->verticalSizeClass;
    }

    /**
     * @param string $verticalSizeClass
     *
     * @return static
     */
    public function setVerticalSizeClass(?string $verticalSizeClass = null): self
    {
        if (null !== $verticalSizeClass) {
            Assertion::inArray($verticalSizeClass, $this->validVerticalSizeClass);
        }

        $this->verticalSizeClass = $verticalSizeClass;
        return $this;
    }

    /**
     * @return string
     */
    public function getViewLocation(): ?string
    {
        return $this->viewLocation;
    }

    /**
     * @param string $viewLocation
     *
     * @return static
     */
    public function setViewLocation(?string $viewLocation = null): self
    {
        if (null !== $viewLocation) {
            Assertion::inArray($viewLocation, $this->validViewLocation);
        }

        $this->viewLocation = $viewLocation;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize(): mixed
    {
        return $this->getSetProperties();
    }
}
