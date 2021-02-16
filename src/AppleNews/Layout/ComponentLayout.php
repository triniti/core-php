<?php
declare(strict_types=1);

namespace Triniti\AppleNews\Layout;

use Assert\Assertion;
use Triniti\AppleNews\AppleNewsObject;
use Triniti\AppleNews\Style\Padding;

/**
 * @link https://developer.apple.com/documentation/apple_news/component_layout
 */
class ComponentLayout extends AppleNewsObject
{
    /** @var int */
    protected $columnSpan;

    /** @var int */
    protected $columnStart;

    /** @var ConditionalComponentLayout[] */
    protected $conditional;

    /** @var boolean|ContentInset */
    protected $contentInset;

    /** @var string */
    protected $horizontalContentAlignment;

    /** @var boolean|string */
    protected $ignoreDocumentGutter;

    /** @var boolean|string */
    protected $ignoreDocumentMargin;

    /** @var boolean|string */
    protected $ignoreViewportPadding;

    /** @var int|Margin */
    protected $margin;

    /** @var int|string */
    protected $maximumWidth;

    /** @var int|string */
    protected $maximumContentWidth;

    /** @var int|string */
    protected $minimumHeight;

    /** @var int|string */
    protected $minimumWidth;

    /** @var int|string|Padding */
    protected $padding;

    /**
     * Valid horizontal content alignment values
     *
     * @var string[]
     */
    private $validHorizontalContentAlignment = [
        'center',
        'left',
        'right',
    ];

    /** @var array */
    private $validIgnoreDocumentGutter = [
        true,
        false,
        'none',
        'left',
        'right',
        'both',
    ];

    /**
     * Valid ignore document gutter margin values
     *
     * @var array
     */
    private $validIgnoreDocumentMargin = [
        true,
        false,
        'none',
        'left',
        'right',
        'both',
    ];

    /**
     * Valid ignore viewport padding values
     *
     * @var array
     */
    private $validIgnoreViewportPadding = [
        true,
        false,
        'none',
        'left',
        'right',
        'both',
    ];

    /**
     * @return int
     */
    public function getColumnSpan(): ?int
    {
        return $this->columnSpan;
    }

    /**
     * @param int $columnSpan
     *
     * @return static
     */
    public function setColumnSpan(int $columnSpan): self
    {
        Assertion::greaterOrEqualThan($columnSpan, 1);
        $this->columnSpan = $columnSpan;
        return $this;
    }

    /**
     * @return int
     */
    public function getColumnStart(): ?int
    {
        return $this->columnStart;
    }

    /**
     * @param int $columnStart
     *
     * @return static
     */
    public function setColumnStart(int $columnStart): self
    {
        Assertion::greaterOrEqualThan($columnStart, 0);
        $this->columnStart = $columnStart;
        return $this;
    }

    /**
     * @return ConditionalComponentLayout[]
     */
    public function getConditional(): array
    {
        return $this->conditional;
    }

    /**
     * @param ConditionalComponentLayout[] $conditionals
     *
     * @return static
     */
    public function setConditional(?array $conditionals = []): self
    {
        $this->conditional = [];

        if (null !== $conditionals) {
            foreach ($conditionals as $conditional) {
                $this->addConditional($conditional);
            }
        }

        return $this;
    }

    /**
     * @param ConditionalComponentLayout $conditional
     *
     * @return static
     */
    public function addConditional(?ConditionalComponentLayout $conditional = null): self
    {
        if (null !== $conditional) {
            $conditional->validate();
            $this->conditional[] = $conditional;
        }

        return $this;
    }

    /**
     * @param ConditionalComponentLayout[] $conditionals
     *
     * @return static
     */
    public function addConditionals(?array $conditionals = []): self
    {
        if (null !== $conditionals) {
            foreach ($conditionals as $conditional) {
                $this->addConditional($conditional);
            }
        }

        return $this;
    }

    /**
     * @return bool|ContentInset
     */
    public function getContentInset()
    {
        return $this->contentInset;
    }

    /**
     * @param boolean|ContentInset|null $contentInset
     *
     * @return static
     */
    public function setContentInset($contentInset = true): self
    {
        $this->contentInset = $contentInset;
        return $this;
    }

    /**
     * @return string
     */
    public function getHorizontalContentAlignment(): ?string
    {
        return $this->horizontalContentAlignment;
    }

    /**
     * @param string $horizontalContentAlignment
     *
     * @return static
     */
    public function setHorizontalContentAlignment(string $horizontalContentAlignment = 'center'): self
    {
        Assertion::inArray($horizontalContentAlignment, $this->validHorizontalContentAlignment);
        $this->horizontalContentAlignment = $horizontalContentAlignment;
        return $this;
    }

    /**
     * @return bool|string
     */
    public function getIgnoreDocumentGutter()
    {
        return $this->ignoreDocumentGutter;
    }

    /**
     * @param bool|string $ignoreDocumentGutter
     *
     * @return static
     */
    public function setIgnoreDocumentGutter($ignoreDocumentGutter = 'none'): self
    {
        Assertion::inArray($ignoreDocumentGutter, $this->validIgnoreDocumentGutter);
        $this->ignoreDocumentGutter = $ignoreDocumentGutter;
        return $this;
    }

    /**
     * @return bool|string
     */
    public function getIgnoreDocumentMargin()
    {
        return $this->ignoreDocumentMargin;
    }

    /**
     * @param bool|string $ignoreViewportPadding
     *
     * @return static
     */
    public function setIgnoreViewportPadding($ignoreViewportPadding = null): self
    {
        Assertion::inArray($ignoreViewportPadding, $this->validIgnoreViewportPadding);
        $this->ignoreViewportPadding = $ignoreViewportPadding;
        return $this;
    }

    /**
     * @return bool|string
     */
    public function getIgnoreViewportPadding()
    {
        return $this->ignoreViewportPadding;
    }

    /**
     * @param bool|string $ignoreDocumentMargin
     *
     * @return static
     */
    public function setIgnoreDocumentMargin($ignoreDocumentMargin = null): self
    {
        Assertion::inArray($ignoreDocumentMargin, $this->validIgnoreDocumentMargin);
        $this->ignoreDocumentMargin = $ignoreDocumentMargin;
        return $this;
    }

    /**
     * @return int|Margin
     */
    public function getMargin()
    {
        return $this->margin;
    }

    /**
     * @param int|Margin $margin
     *
     * @return static
     */
    public function setMargin($margin): self
    {
        $this->margin = $margin;
        return $this;
    }

    /**
     * @return int|string
     */
    public function getMaximumWidth()
    {
        return $this->maximumWidth;
    }

    /**
     * @param int|string $width
     *
     * @return static
     */
    public function setMaximumWidth($width): self
    {
        $this->maximumWidth = $width;
        return $this;
    }

    /**
     * @return int|string
     */
    public function getMaximumContentWidth()
    {
        return $this->maximumContentWidth;
    }

    /**
     * @param int|string $maximumContentWidth
     *
     * @return static
     */
    public function setMaximumContentWidth($maximumContentWidth): self
    {
        $this->maximumContentWidth = $maximumContentWidth;
        return $this;
    }

    /**
     * @return int|string
     */
    public function getMinimumHeight()
    {
        return $this->minimumHeight;
    }

    /**
     * @param int|string $minimumHeight
     *
     * @return static
     */
    public function setMinimumHeight($minimumHeight): self
    {
        $this->minimumHeight = $minimumHeight;
        return $this;
    }

    /**
     * @return int|string
     */
    public function getMinimumWidth()
    {
        return $this->minimumWidth;
    }

    /**
     * @param int|string $width
     *
     * @return static
     */
    public function setMinimumWidth($width): self
    {
        $this->minimumWidth = $width;
        return $this;
    }

    /**
     * @return int|string|Padding
     */
    public function getPadding()
    {
        return $this->padding;
    }

    /**
     * @param int|string|Padding $padding
     *
     * @return static
     */
    public function setPadding($padding): self
    {
        $this->padding = $padding;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        return $this->getSetProperties();
    }
}
