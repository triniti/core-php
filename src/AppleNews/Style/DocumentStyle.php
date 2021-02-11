<?php
declare(strict_types=1);

namespace Triniti\AppleNews\Style;

use Triniti\AppleNews\AppleNewsObject;

/**
 * @link https://developer.apple.com/documentation/apple_news/documentstyle
 */
class DocumentStyle extends AppleNewsObject
{
    protected string $backgroundColor = 'white';

    public function getBackgroundColor(): string
    {
        return $this->backgroundColor;
    }

    public function setBackgroundColor(string $backgroundColor = 'white'): self
    {
        $this->backgroundColor = $backgroundColor;
        return $this;
    }

    public function jsonSerialize()
    {
        return $this->getSetProperties();
    }
}

