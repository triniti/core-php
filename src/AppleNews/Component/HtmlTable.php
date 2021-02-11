<?php
declare(strict_types=1);

namespace Triniti\AppleNews\Component;

use Assert\Assertion;

/**
 * @link https://developer.apple.com/documentation/apple_news/html_table
 */
class HtmlTable extends Component
{
    protected ?string $html = null;

    public function getHtml(): ?string
    {
        return $this->html;
    }

    public function setHtml(string $html): self
    {
        Assertion::regex($html, '/^<table>.+?<\/table>$/s', 'Html must begin with <table> and end with </table>.');
        $this->html = $html;
        return $this;
    }

    public function validate(): void
    {
        Assertion::notNull($this->html);
    }

    public function jsonSerialize()
    {
        $properties = $this->getSetProperties();
        $properties['role'] = 'htmltable';
        return $properties;
    }
}
