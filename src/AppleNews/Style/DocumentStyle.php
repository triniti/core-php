<?php
declare(strict_types=1);

namespace Triniti\AppleNews\Style;

use Triniti\AppleNews\AppleNewsObject;

/**
 * @link https://developer.apple.com/documentation/apple_news/documentstyle
 */
class DocumentStyle extends AppleNewsObject
{
    /** @var string */
    protected $backgroundColor = 'white';

    /**
     * @return string
     */
    public function getBackgroundColor(): string
    {
        return $this->backgroundColor;
    }

    /**
     * @param string $backgroundColor
     *
     * @return static
     */
    public function setBackgroundColor(string $backgroundColor = 'white'): self
    {
        $this->backgroundColor = $backgroundColor;
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

