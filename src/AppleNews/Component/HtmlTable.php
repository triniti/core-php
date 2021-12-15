<?php
declare(strict_types=1);

namespace Triniti\AppleNews\Component;

use Assert\Assertion;

/**
 * @link https://developer.apple.com/documentation/apple_news/html_table
 */
class HtmlTable extends Component
{
    /** @var string */
    protected $html;

    /**
     * @return string
     */
    public function getHtml(): ?string
    {
        return $this->html;
    }

    /**
     * @param string $html
     *
     * @return static
     */
    public function setHtml(string $html): self
    {
        Assertion::regex($html, '/^<table>.+?<\/table>$/s', 'Html must begin with <table> and end with </table>.');
        $this->html = $html;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function validate(): void
    {
        Assertion::notNull($this->html);
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize(): mixed
    {
        $properties = $this->getSetProperties();
        $properties['role'] = 'htmltable';
        return $properties;
    }
}
