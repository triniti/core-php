<?php

namespace Triniti\Tests\AppleNews\Layout;

use Assert\AssertionFailedException;
use PHPUnit\Framework\TestCase;
use Triniti\AppleNews\Animation\AppearAnimation;
use Triniti\AppleNews\Behavior\Motion;
use Triniti\AppleNews\Component\Author;
use Triniti\AppleNews\Layout\Anchor;
use Triniti\AppleNews\Layout\ComponentLayout;
use Triniti\AppleNews\Link\Link;
use Triniti\AppleNews\Style\ComponentStyle;
use Triniti\AppleNews\Style\ComponentTextStyle;
use Triniti\AppleNews\Style\InlineTextStyle;

class AuthorTextComponentTest extends TestCase
{
    protected Author $author;

    public function setup(): void
    {
        $this->author = new Author();
    }

    public function testCreateAuthor(): void
    {
        $this->assertInstanceOf(
            'Triniti\AppleNews\Component\Author',
            $this->author
        );
    }

    /* Author extends Text so we will use this class to test Text abstract class and Component abstract class */
    public function testGetSetText(): void
    {
        $this->author->setText('test');
        $this->assertEquals('test', $this->author->getText());

        $this->author->setText();
        $this->assertNull($this->author->getText());
    }

    public function testGetSetAddAddition(): void
    {
        $link = new Link();
        $link->setURL('http://test.com');
        $link->setRangeLength(1);
        $link->setRangeStart(0);

        $link2 = new Link();
        $link2->setURL('http://test2.com');
        $link2->setRangeLength(1);
        $link2->setRangeStart(0);
        $additions[] = $link;

        $this->author->setAdditions($additions);
        $this->assertEquals($additions, $this->author->getAdditions());

        $additions[] = $link2;
        $this->author->addAdditions($additions);
        $this->assertEquals([$link, $link, $link2], $this->author->getAdditions());

        $this->author->addAdditions();
        $this->assertEquals([$link, $link, $link2], $this->author->getAdditions());

        $this->author->addAdditions(null);
        $this->assertEquals([$link, $link, $link2], $this->author->getAdditions());

        $this->author->setAdditions();
        $this->assertEquals([], $this->author->getAdditions());

        $this->author->setAdditions(null);
        $this->assertEquals([], $this->author->getAdditions());
    }

    public function testGetSetFormat(): void
    {
        $this->author->setFormat('html');
        $this->assertEquals('html', $this->author->getFormat());

        $this->author->setFormat();
        $this->assertEquals('none', $this->author->getFormat());
    }

    public function testSetInvalidFormat(): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->author->setFormat('test');
    }

    public function testGetSetAddInlineTextStyle(): void
    {
        $inlineTextStyle = new InlineTextStyle();
        $inlineTextStyle->setTextStyle('test');
        $inlineTextStyle->setRangeLength(1);
        $inlineTextStyle->setRangeStart(0);

        $inlineTextStyle2 = new InlineTextStyle();
        $inlineTextStyle2->setTextStyle('test2');
        $inlineTextStyle2->setRangeLength(1);
        $inlineTextStyle2->setRangeStart(0);

        $inlineTextStyles[] = $inlineTextStyle;

        $this->author->setInlineTextStyles($inlineTextStyles);
        $this->assertEquals($inlineTextStyles, $this->author->getInlineTextStyles());

        $inlineTextStyles[] = $inlineTextStyle2;
        $this->author->addInlineTextStyles($inlineTextStyles);
        $this->assertEquals([$inlineTextStyle, $inlineTextStyle, $inlineTextStyle2], $this->author->getInlineTextStyles());

        $this->author->addInlineTextStyles();
        $this->assertEquals([$inlineTextStyle, $inlineTextStyle, $inlineTextStyle2], $this->author->getInlineTextStyles());

        $this->author->addInlineTextStyles(null);
        $this->assertEquals([$inlineTextStyle, $inlineTextStyle, $inlineTextStyle2], $this->author->getInlineTextStyles());

        $this->author->setInlineTextStyles();
        $this->assertEquals([], $this->author->getInlineTextStyles());

        $this->author->setInlineTextStyles(null);
        $this->assertEquals([], $this->author->getInlineTextStyles());
    }

    public function testGetSetTextStyle(): void
    {
        $this->author->setTextStyle('test');
        $this->assertEquals('test', $this->author->getTextStyle());

        $componentTextStyle = new ComponentTextStyle();
        $this->author->setTextStyle($componentTextStyle);
        $this->assertEquals($componentTextStyle, $this->author->getTextStyle());

        $this->author->setTextStyle(null);
        $this->assertNull($this->author->getTextStyle());
    }

    public function testSetInvalidTextStyle(): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->author->setTextStyle(1);
    }

    public function testGetSetAnchor(): void
    {
        $anchor = new Anchor();
        $anchor->setTargetAnchorPosition('top');

        $this->author->setAnchor($anchor);
        $this->assertEquals($anchor, $this->author->getAnchor());

        $this->author->setAnchor();
        $this->assertNull($this->author->getAnchor());
    }

    public function testGetSetAnimation(): void
    {
        $animation = new AppearAnimation();

        $this->author->setAnimation($animation);
        $this->assertEquals($animation, $this->author->getAnimation());

        $this->author->setAnimation();
        $this->assertNull($this->author->getAnimation());
    }

    public function testGetSetBehavior(): void
    {
        $behavior = new Motion();

        $this->author->setBehavior($behavior);
        $this->assertEquals($behavior, $this->author->getBehavior());

        $this->author->setBehavior();
        $this->assertNull($this->author->getBehavior());
    }

    public function testGetSetIdentifier(): void
    {
        $this->author->setIdentifier('test');
        $this->assertEquals('test', $this->author->getIdentifier());

        $this->author->setIdentifier();
        $this->assertNull($this->author->getIdentifier());
    }

    public function testGetSetLayout(): void
    {
        $this->author->setLayout('test');
        $this->assertEquals('test', $this->author->getLayout());

        $componentLayout = new ComponentLayout();
        $this->author->setLayout($componentLayout);
        $this->assertEquals($componentLayout, $this->author->getLayout());

        $this->author->setLayout(null);
        $this->assertNull($this->author->getLayout());
    }

    public function testGetSetStyle(): void
    {
        $this->author->setStyle('test');
        $this->assertEquals('test', $this->author->getStyle());

        $componentStyle = new ComponentStyle();
        $this->author->setStyle($componentStyle);
        $this->assertEquals($componentStyle, $this->author->getStyle());

        $this->author->setStyle(null);
        $this->assertNull($this->author->getStyle());
    }

    public function testValidate(): void
    {
        $this->expectException(AssertionFailedException::class);
        $a = new Author();
        $a->validate();
    }

    public function testJsonSerialize(): void
    {
        $link = new Link();
        $link->setURL('http://test.com');
        $link->setRangeLength(1);
        $link->setRangeStart(0);

        $inlineTextStyle = new InlineTextStyle();
        $inlineTextStyle->setTextStyle('test');
        $inlineTextStyle->setRangeLength(1);
        $inlineTextStyle->setRangeStart(0);

        $anchor = new Anchor();
        $anchor->setTargetAnchorPosition('top');

        $expected = [
            'role'             => 'author',
            'text'             => 'test',
            'format'           => 'html',
            'additions'        => [$link],
            'textStyle'        => 'textStyle',
            'inlineTextStyles' => [$inlineTextStyle],
            'anchor'           => $anchor,
            'animation'        => new AppearAnimation(),
            'behavior'         => new Motion(),
            'identifier'       => 'test',
            'layout'           => 'layout',
            'style'            => 'style',

        ];

        $this->author
            ->setText('test')
            ->setFormat('html')
            ->setAdditions([$link])
            ->setTextStyle('textStyle')
            ->setInlineTextStyles([$inlineTextStyle])
            ->setAnchor($anchor)
            ->setAnimation(new AppearAnimation())
            ->setBehavior(new Motion())
            ->setIdentifier('test')
            ->setLayout('layout')
            ->setStyle('style');

        $this->assertJsonStringEqualsJsonString(json_encode($expected), json_encode($this->author));
    }
}
