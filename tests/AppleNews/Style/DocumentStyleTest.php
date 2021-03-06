<?php
declare(strict_types=1);

namespace Triniti\Tests\AppleNews\Style;

use PHPUnit\Framework\TestCase;
use Triniti\AppleNews\Style\DocumentStyle;

class DocumentStyleTest extends TestCase
{
    protected DocumentStyle $documentStyle;

    public function setUp(): void
    {
        $this->documentStyle = new DocumentStyle();
    }

    public function testCreateDocumentStyle(): void
    {
        $this->assertInstanceOf('Triniti\AppleNews\Style\DocumentStyle', $this->documentStyle);
    }

    public function testJsonSerialize(): void
    {
        $this->documentStyle->setBackgroundColor('#F7F7F7');
        $expectedJson = '{"backgroundColor":"#F7F7F7"}';
        $this->assertJsonStringEqualsJsonString($expectedJson, json_encode($this->documentStyle));
    }
}


