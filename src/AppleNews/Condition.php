<?php

declare(strict_types=1);

namespace Triniti\AppleNews;

use Assert\Assertion;

/**
 * @link https://developer.apple.com/documentation/apple_news/condition
 */
class Condition extends AppleNewsObject
{
    protected ?float $maxViewportAspectRatio = null;
    protected ?float $minViewportAspectRatio = null;
    protected ?int $maxColumns = null;
    protected ?int $maxViewportWidth = null;
    protected ?int $minColumns = null;
    protected ?int $minViewportWidth = null;
    protected ?string $horizontalSizeClass = null;
    protected ?string $maxContentSizeCategory = null;
    protected ?string $maxSpecVersion = null;
    protected ?string $minContentSizeCategory = null;
    protected ?string $minSpecVersion = null;
    protected ?string $platform = null;
    protected ?string $preferredColorScheme = null;
    protected ?string $subscriptionStatus = null;
    protected ?string $verticalSizeClass = null;
    protected ?string $viewLocation = null;

    /**
     * @var string[]
     */
    private array $validHorizontalSizeClass = [
        'any',
        'regular',
        'compact',
    ];

    /**
     * @var string[]
     */
    private array $validContentSizeCategory = [
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
    private array $validPlatform = [
        'any',
        'ios',
        'macos',
        'web',
    ];

    /**
     * @var string[]
     */
    private array $validPreferredColorScheme = [
        'any',
        'light',
        'dark',
    ];

    /**
     * @var string[]
     */
    private array $validSubscriptionStatus = [
        'bundle',
        'subscribed',
    ];

    /**
     * @var string[]
     */
    private array $validVerticalSizeClass = [
        'any',
        'regular',
        'compact',
    ];

    /**
     * @var string[]
     */
    private array $validViewLocation = [
        'any',
        'article',
        'issue_table_of_contents',
        'issue',
    ];

    public function getHorizontalSizeClass(): ?string
    {
        return $this->horizontalSizeClass;
    }

    public function setHorizontalSizeClass(?string $horizontalSizeClass = null): self
    {
        if (null !== $horizontalSizeClass) {
            Assertion::inArray($horizontalSizeClass, $this->validHorizontalSizeClass);
        }

        $this->horizontalSizeClass = $horizontalSizeClass;
        return $this;
    }

    public function getMaxColumns(): ?int
    {
        return $this->maxColumns;
    }

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

    public function getMaxContentSizeCategory(): ?string
    {
        return $this->maxContentSizeCategory;
    }

    public function setMaxContentSizeCategory(?string $maxContentSizeCategory = null): self
    {
        if (null !== $maxContentSizeCategory) {
            Assertion::inArray($maxContentSizeCategory, $this->validContentSizeCategory);
        }

        $this->maxContentSizeCategory = $maxContentSizeCategory;
        return $this;
    }

    public function getMaxSpecVersion(): ?string
    {
        return $this->maxSpecVersion;
    }

    public function setMaxSpecVersion(?string $maxSpecVersion = null): self
    {
        $this->maxSpecVersion = $maxSpecVersion;
        return $this;
    }

    public function getMaxViewportAspectRatio(): ?float
    {
        return $this->maxViewportAspectRatio;
    }

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

    public function getMaxViewportWidth(): ?int
    {
        return $this->maxViewportWidth;
    }

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

    public function getMinColumns(): ?int
    {
        return $this->minColumns;
    }

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

    public function getMinContentSizeCategory(): ?string
    {
        return $this->minContentSizeCategory;
    }

    public function setMinContentSizeCategory(?string $minContentSizeCategory = null): self
    {
        if (null !== $minContentSizeCategory) {
            Assertion::inArray($minContentSizeCategory, $this->validContentSizeCategory);
        }

        $this->minContentSizeCategory = $minContentSizeCategory;
        return $this;
    }

    public function getMinSpecVersion(): ?string
    {
        return $this->minSpecVersion;
    }

    public function setMinSpecVersion(?string $minSpecVersion = null): self
    {
        $this->minSpecVersion = $minSpecVersion;
        return $this;
    }

    public function getMinViewportAspectRatio(): ?float
    {
        return $this->minViewportAspectRatio;
    }

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

    public function getMinViewportWidth(): ?int
    {
        return $this->minViewportWidth;
    }

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

    public function getPlatform(): ?string
    {
        return $this->platform;
    }

    public function setPlatform(?string $platform = null): self
    {
        if (null !== $platform) {
            Assertion::inArray($platform, $this->validPlatform);
        }

        $this->platform = $platform;
        return $this;
    }

    public function getPreferredColorScheme(): ?string
    {
        return $this->preferredColorScheme;
    }

    public function setPreferredColorScheme(?string $preferredColorScheme = null): self
    {
        if (null !== $preferredColorScheme) {
            Assertion::inArray($preferredColorScheme, $this->validPreferredColorScheme);
        }

        $this->preferredColorScheme = $preferredColorScheme;
        return $this;
    }

    public function getSubscriptionStatus(): ?string
    {
        return $this->subscriptionStatus;
    }

    public function setSubscriptionStatus(?string $subscriptionStatus = null): self
    {
        if (null !== $subscriptionStatus) {
            Assertion::inArray($subscriptionStatus, $this->validSubscriptionStatus);
        }

        $this->subscriptionStatus = $subscriptionStatus;
        return $this;
    }

    public function getVerticalSizeClass(): ?string
    {
        return $this->verticalSizeClass;
    }

    public function setVerticalSizeClass(?string $verticalSizeClass = null): self
    {
        if (null !== $verticalSizeClass) {
            Assertion::inArray($verticalSizeClass, $this->validVerticalSizeClass);
        }

        $this->verticalSizeClass = $verticalSizeClass;
        return $this;
    }

    public function getViewLocation(): ?string
    {
        return $this->viewLocation;
    }

    public function setViewLocation(?string $viewLocation = null): self
    {
        if (null !== $viewLocation) {
            Assertion::inArray($viewLocation, $this->validViewLocation);
        }

        $this->viewLocation = $viewLocation;
        return $this;
    }

    public function jsonSerialize()
    {
        return $this->getSetProperties();
    }
}
