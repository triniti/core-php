<?php
declare(strict_types=1);

namespace Triniti\Tests\AppleNews;

use Assert\InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Triniti\AppleNews\AdvertisingSettings;
use Triniti\AppleNews\ArticleDocument;
use Triniti\AppleNews\AutoPlacement;
use Triniti\AppleNews\Component\Title;
use Triniti\AppleNews\Layout\ComponentLayout;
use Triniti\AppleNews\Layout\Layout;
use Triniti\AppleNews\Style\ComponentStyle;
use Triniti\AppleNews\Style\ComponentTextStyle;
use Triniti\AppleNews\Style\InlineTextStyle;
use Triniti\AppleNews\Style\TextStyle;

class ArticleDocumentTest extends TestCase
{
    protected ArticleDocument $articleDocument;

    public function setUp(): void
    {
        $this->articleDocument = new ArticleDocument();
    }

    public function testCreateArticleDocument(): void
    {
        $this->assertInstanceOf('Triniti\AppleNews\ArticleDocument',
            $this->articleDocument);
    }

    public function testGetSetLanguage(): void
    {
        $this->articleDocument->setLanguage('foo');
        $this->assertSame('foo', $this->articleDocument->getLanguage());
    }

    public function testGetSetAutoPlacement(): void
    {
        $this->assertNull($this->articleDocument->getAutoPlacement());
        $autoPlacement = new AutoPlacement();
        $this->articleDocument->setAutoPlacement($autoPlacement);
        $this->assertSame($autoPlacement, $this->articleDocument->getAutoPlacement());
    }

    public function testGetSetAdvertisingSettings(): void
    {
        $this->assertNull($this->articleDocument->getAdvertisingSettings());
        $advertisingSettings = new AdvertisingSettings();
        $this->articleDocument->setAdvertisingSettings($advertisingSettings);
        $this->assertSame($advertisingSettings, $this->articleDocument->getAdvertisingSettings());
    }

    public function testGetSetSubtitle(): void
    {
        $this->assertNull($this->articleDocument->getSubtitle());
        $this->articleDocument->setSubtitle('foo');
        $this->assertSame('foo', $this->articleDocument->getSubtitle());
    }

    /**
     * @param ArticleDocument $articleDocument
     *
     * @dataProvider providerTestMissingGlobalSettings
     */
    public function testMissingGlobalSettings(ArticleDocument $articleDocument)
    {
        $this->expectException(InvalidArgumentException::class);
        $articleDocument->validate();
    }

    public function testJsonSerialize(): void
    {
        $this->articleDocument->setIdentifier('identifier');
        $this->articleDocument->setTitle('title');
        $layout = new Layout();
        $layout->setColumns(1);
        $layout->setWidth(1);
        $this->articleDocument->setLayout($layout);
        $titleComponent = new Title();
        $titleComponent->setText('text');
        $this->articleDocument->addComponent($titleComponent);

        $expectedJson = '{"version":"1.10","identifier":"identifier","language":"en","title":"title","layout":{"columns":1,"width":1},"components":[{"role":"title","text":"text"}]}';
        $this->assertJsonStringEqualsJsonString($expectedJson, json_encode($this->articleDocument));
    }

    public function providerTestMissingGlobalSettings(): array
    {
        $docMissingComponentLayout = new ArticleDocument();
        $docMissingComponentLayout->setIdentifier('identifier');
        $docMissingComponentLayout->setTitle('title');
        $layout = new Layout();
        $layout->setColumns(1);
        $layout->setWidth(1);
        $docMissingComponentLayout->setLayout($layout);
        $titleComponent = new Title();
        $titleComponent->setText('title');
        $titleComponent->setLayout('component-layout');
        $docMissingComponentLayout->addComponent($titleComponent);
        $componentLayoutObject = new ComponentLayout();
        $docMissingComponentLayout->setComponentLayout('component-layout-one', $componentLayoutObject);

        $docMissingComponentStyle = new ArticleDocument();
        $docMissingComponentStyle->setIdentifier('identifier');
        $docMissingComponentStyle->setTitle('title');
        $layout = new Layout();
        $layout->setColumns(1);
        $layout->setWidth(1);
        $docMissingComponentStyle->setLayout($layout);
        $titleComponent2 = new Title();
        $titleComponent2->setText('title');
        $titleComponent2->setStyle('component-style');
        $docMissingComponentStyle->addComponent($titleComponent2);
        $componentStyle = new ComponentStyle();
        $docMissingComponentStyle->setComponentStyle('component-style-one', $componentStyle);

        $docMissingComponentTextStyle = new ArticleDocument();
        $docMissingComponentTextStyle->setIdentifier('identifier');
        $docMissingComponentTextStyle->setTitle('title');
        $layout = new Layout();
        $layout->setColumns(1);
        $layout->setWidth(1);
        $docMissingComponentTextStyle->setLayout($layout);
        $titleComponent2 = new Title();
        $titleComponent2->setText('title');
        $titleComponent2->setTextStyle('component-textstyle');
        $docMissingComponentTextStyle->addComponent($titleComponent2);
        $componentTextStyle = new ComponentTextStyle();
        $docMissingComponentTextStyle->setComponentTextStyle('component-textstyle-one', $componentTextStyle);

        $docMissinginlineTextStyle = new ArticleDocument();
        $docMissinginlineTextStyle->setIdentifier('identifier');
        $docMissinginlineTextStyle->setTitle('title');
        $layout = new Layout();
        $layout->setColumns(1);
        $layout->setWidth(1);
        $docMissinginlineTextStyle->setLayout($layout);
        $titleComponent2 = new Title();
        $titleComponent2->setText('title');
        $inlineTextStyle = new InlineTextStyle();
        $inlineTextStyle->setRangeLength(2);
        $inlineTextStyle->setRangeStart(1);
        $inlineTextStyle->setTextStyle('inline-text-style');
        $titleComponent2->setInlineTextStyles([$inlineTextStyle]);
        $docMissinginlineTextStyle->addComponent($titleComponent2);
        $textStyle = new TextStyle();
        $docMissinginlineTextStyle->setTextStyle('inline-text-style2', $textStyle);

        return [
            [$docMissingComponentLayout],
            [$docMissingComponentStyle],
            [$docMissingComponentTextStyle],
            [$docMissinginlineTextStyle],
        ];
    }
}


