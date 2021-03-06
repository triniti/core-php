<?php
declare(strict_types=1);

namespace Triniti\AppleNews\Style;

use Assert\Assertion;
use Triniti\AppleNews\AppleNewsObject;

/**
 * @link https://developer.apple.com/documentation/apple_news/drop_cap_style?language=data
 */
class DropCapStyle extends AppleNewsObject
{
    /** @var string */
    protected $backgroundColor;

    /** @var string */
    protected $fontName;

    /** @var int */
    protected $numberOfCharacters = 1;

    /** @var int */
    protected $numberOfLines;

    /** @var int */
    protected $numberOfRaisedLines;

    /** @var int */
    protected $padding = 0;

    /** @var string */
    protected $textColor;

    /**
     * @return string
     */
    public function getBackgroundColor(): ?string
    {
        return $this->backgroundColor;
    }

    /**
     * @param string $backgroundColor
     *
     * @return static
     */
    public function setBackgroundColor(?string $backgroundColor = null): self
    {
        $this->backgroundColor = $backgroundColor;
        return $this;
    }

    /**
     * @return string
     */
    public function getFontName(): ?string
    {
        return $this->fontName;
    }

    /**
     * @param string $fontName
     *
     * @return static
     */
    public function setFontName(?string $fontName = null): self
    {
        $this->fontName = $fontName;
        return $this;
    }

    /**
     * @return int
     */
    public function getNumberOfCharacters(): int
    {
        return $this->numberOfCharacters;
    }

    /**
     * @param int $numberOfCharacters
     *
     * @return static
     */
    public function setNumberOfCharacters(int $numberOfCharacters = 1): self
    {
        Assertion::greaterOrEqualThan($numberOfCharacters, 1);
        Assertion::lessOrEqualThan($numberOfCharacters, 4);
        $this->numberOfCharacters = $numberOfCharacters;
        return $this;
    }

    /**
     * @return int
     */
    public function getNumberOfLines(): ?int
    {
        return $this->numberOfLines;
    }

    /**
     * @param int $numberOfLines
     *
     * @return static
     */
    public function setNumberOfLines(int $numberOfLines): self
    {
        Assertion::greaterOrEqualThan($numberOfLines, 2);
        Assertion::lessOrEqualThan($numberOfLines, 10);
        $this->numberOfLines = $numberOfLines;
        return $this;
    }

    /**
     * @return int
     */
    public function getNumberOfRaisedLines(): ?int
    {
        return $this->numberOfRaisedLines;
    }

    /**
     * @param int $numberOfRaisedLines
     *
     * @return static
     */
    public function setNumberOfRaisedLines(?int $numberOfRaisedLines = null): self
    {
        $this->numberOfRaisedLines = $numberOfRaisedLines;
        return $this;
    }

    /**
     * @return int
     */
    public function getPadding()
    {
        return $this->padding;
    }

    /**
     * @param int $padding
     *
     * @return static
     */
    public function setPadding(int $padding = 0): self
    {
        $this->padding = $padding;
        return $this;
    }

    /**
     * @return string
     */
    public function getTextColor(): ?string
    {
        return $this->textColor;
    }

    /**
     * @param string $textColor
     *
     * @return static
     */
    public function setTextColor(?string $textColor = null): self
    {
        $this->textColor = $textColor;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        return $this->getSetProperties();
    }

    /**
     * {@inheritdoc}
     */
    public function validate(): void
    {
        Assertion::notNull($this->numberOfLines);
    }
}
