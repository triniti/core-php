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
    protected ?string $backgroundColor = null;
    protected ?string $fontName = null;
    protected int $numberOfCharacters = 1;
    protected ?int $numberOfLines = null;
    protected ?int $numberOfRaisedLines = null;
    protected int $padding = 0;
    protected ?string $textColor = null;

    public function getBackgroundColor(): ?string
    {
        return $this->backgroundColor;
    }

    public function setBackgroundColor(?string $backgroundColor = null): self
    {
        $this->backgroundColor = $backgroundColor;
        return $this;
    }

    public function getFontName(): ?string
    {
        return $this->fontName;
    }

    public function setFontName(?string $fontName = null): self
    {
        $this->fontName = $fontName;
        return $this;
    }

    public function getNumberOfCharacters(): int
    {
        return $this->numberOfCharacters;
    }

    public function setNumberOfCharacters(int $numberOfCharacters = 1): self
    {
        Assertion::greaterOrEqualThan($numberOfCharacters, 1);
        Assertion::lessOrEqualThan($numberOfCharacters, 4);
        $this->numberOfCharacters = $numberOfCharacters;
        return $this;
    }

    public function getNumberOfLines(): ?int
    {
        return $this->numberOfLines;
    }

    public function setNumberOfLines(int $numberOfLines): self
    {
        Assertion::greaterOrEqualThan($numberOfLines, 2);
        Assertion::lessOrEqualThan($numberOfLines, 10);
        $this->numberOfLines = $numberOfLines;
        return $this;
    }

    public function getNumberOfRaisedLines(): ?int
    {
        return $this->numberOfRaisedLines;
    }

    public function setNumberOfRaisedLines(?int $numberOfRaisedLines = null): self
    {
        $this->numberOfRaisedLines = $numberOfRaisedLines;
        return $this;
    }

    public function getPadding()
    {
        return $this->padding;
    }

    public function setPadding(int $padding = 0): self
    {
        $this->padding = $padding;
        return $this;
    }

    public function getTextColor(): ?string
    {
        return $this->textColor;
    }

    public function setTextColor(?string $textColor = null): self
    {
        $this->textColor = $textColor;
        return $this;
    }

    public function jsonSerialize()
    {
        return $this->getSetProperties();
    }

    public function validate(): void
    {
        Assertion::notNull($this->numberOfLines);
    }
}
