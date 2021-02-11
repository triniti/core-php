<?php

namespace Triniti\Tests\AppleNews\Layout;

use Assert\AssertionFailedException;
use PHPUnit\Framework\TestCase;
use Triniti\AppleNews\Component\HtmlTable;

class HtmlTableTest extends TestCase
{
    protected HtmlTable $htmlTable;

    public function setUp(): void
    {
        $this->htmlTable = new HtmlTable();
    }

    public function testCreateHtmlTable(): void
    {
        $this->assertInstanceOf(
            'Triniti\AppleNews\Component\HtmlTable',
            $this->htmlTable
        );
    }

    public function testGetSetHtml(): void
    {
        $this->htmlTable->setHtml('<table><tr><td>test</td></tr></table>');
        $this->assertEquals('<table><tr><td>test</td></tr></table>', $this->htmlTable->getHtml());
    }

    public function testSetInvalidHtml(): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->htmlTable->setHtml('test');
    }

    public function testValidate(): void
    {
        $this->expectException(AssertionFailedException::class);
        $htmlTable = new HtmlTable();
        $htmlTable->validate();
    }

    public function testJsonSerialize(): void
    {
        $expected = [
            'role' => 'htmltable',
            'html' => '<table><tr><td>test</td></tr></table>',
        ];

        $this->htmlTable->setHtml('<table><tr><td>test</td></tr></table>');

        $this->assertJsonStringEqualsJsonString(json_encode($expected), json_encode($this->htmlTable));
    }
}
