<?php
declare(strict_types=1);

namespace Triniti\AppleNews\Component;

/**
 * @link https://developer.apple.com/documentation/apple_news/pull_quote
 */
class PullQuote extends Text
{
    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        $properties = $this->getSetProperties();
        $properties['role'] = 'pullquote';
        return $properties;
    }
}
