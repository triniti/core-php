<?php
declare(strict_types=1);

namespace Triniti\Tests\AppleNews\Layout;

use Assert\AssertionFailedException;
use PHPUnit\Framework\TestCase;
use Triniti\AppleNews\Condition;
use Triniti\AppleNews\Layout\ComponentLayout;
use Triniti\AppleNews\Layout\ConditionalComponentLayout;
use Triniti\AppleNews\Layout\ContentInset;
use Triniti\AppleNews\Layout\Margin;
use Triniti\AppleNews\SupportedUnits;

class ComponentLayoutTest extends TestCase
{
    protected ComponentLayout $componentLayout;

    public function setUp(): void
    {
        $this->componentLayout = new ComponentLayout();
    }

    public function testCreateComponentLayout(): void
    {
        $this->assertInstanceOf(
            'Triniti\AppleNews\Layout\ComponentLayout',
            $this->componentLayout
        );

        $this->assertNull($this->componentLayout->getMargin());
        $this->assertNull($this->componentLayout->getColumnSpan());
        $this->assertNull($this->componentLayout->getColumnStart());
        $this->assertNull($this->componentLayout->getContentInset());
        $this->assertNull($this->componentLayout->getHorizontalContentAlignment());
        $this->assertNull($this->componentLayout->getIgnoreDocumentGutter());
        $this->assertNull($this->componentLayout->getIgnoreDocumentMargin());
        $this->assertNull($this->componentLayout->getIgnoreViewportPadding());
        $this->assertNull($this->componentLayout->getMaximumContentWidth());
        $this->assertNull($this->componentLayout->getMinimumHeight());
    }

    public function testSetColumnSpan(): void
    {
        $this->componentLayout->setColumnSpan(3);
        $this->assertEquals(3, $this->componentLayout->getColumnSpan());
    }

    public function testSetColumnStart(): void
    {
        $this->componentLayout->setColumnStart(1);
        $this->assertEquals(1, $this->componentLayout->getColumnStart());
    }

    public function testGetSetAddConditional(): void
    {
        $conditional1 = new ConditionalComponentLayout();
        $condition1 = new Condition();
        $condition1->setMaxColumns(1);
        $conditional1->addCondition($condition1);


        $conditional2 = new ConditionalComponentLayout();
        $condition2 = new Condition();
        $condition2->setPlatform('any');
        $conditional2->addCondition($condition2);

        $conditional = [$conditional1];

        $this->componentLayout->setConditional($conditional);
        $this->assertEquals($conditional, $this->componentLayout->getConditional());

        $conditional[] = $conditional2;
        $this->componentLayout->addConditionals($conditional);
        $this->assertEquals([$conditional1, $conditional1, $conditional2], $this->componentLayout->getConditional());

        $this->componentLayout->addConditionals();
        $this->assertEquals([$conditional1, $conditional1, $conditional2], $this->componentLayout->getConditional());

        $this->componentLayout->addConditionals(null);
        $this->assertEquals([$conditional1, $conditional1, $conditional2], $this->componentLayout->getConditional());

        $this->componentLayout->setConditional();
        $this->assertEquals([], $this->componentLayout->getConditional());

        $this->componentLayout->setConditional(null);
        $this->assertEquals([], $this->componentLayout->getConditional());
    }

    public function testSetContentInset(): void
    {
        $contentInset = new ContentInset();
        $contentInset->setTop()->setBottom();

        $this->componentLayout->setContentInset($contentInset);
        $this->assertSame($contentInset, $this->componentLayout->getContentInset());

        $this->componentLayout->setContentInset(true);
        $this->assertTrue($this->componentLayout->getContentInset());
    }

    public function testSetHorizontalContentAlignment(): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->componentLayout->setHorizontalContentAlignment('left');
        $this->assertEquals('left', $this->componentLayout->getHorizontalContentAlignment());

        $this->componentLayout->setHorizontalContentAlignment('right');
        $this->assertEquals('right', $this->componentLayout->getHorizontalContentAlignment());

        $this->componentLayout->setHorizontalContentAlignment();
        $this->assertEquals('center', $this->componentLayout->getHorizontalContentAlignment());
        $this->componentLayout->setHorizontalContentAlignment('center');
        $this->assertEquals('center', $this->componentLayout->getHorizontalContentAlignment());

        $this->componentLayout->setHorizontalContentAlignment('in_valid_value');
    }

    public function testSetMinimumHeight(): void
    {
        $this->componentLayout->setMinimumHeight(20);
        $this->assertEquals(20, $this->componentLayout->getMinimumHeight());

        $minimumHeight = new SupportedUnits('30pt');
        $this->componentLayout->setMinimumHeight($minimumHeight);
        $this->assertSame($minimumHeight, $this->componentLayout->getMinimumHeight());
    }

    public function testIgnoreDocumentGutter(): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->assertNull($this->componentLayout->getIgnoreDocumentGutter());

        $this->componentLayout->setIgnoreDocumentGutter();
        $this->assertSame('none', $this->componentLayout->getIgnoreDocumentGutter());

        $this->componentLayout->setIgnoreDocumentGutter(true);
        $this->assertTrue($this->componentLayout->getIgnoreDocumentGutter());

        $this->componentLayout->setIgnoreDocumentGutter(false);
        $this->assertFalse($this->componentLayout->getIgnoreDocumentGutter());

        $this->componentLayout->setIgnoreDocumentGutter('none');
        $this->assertEquals('none', $this->componentLayout->getIgnoreDocumentGutter());

        $this->componentLayout->setIgnoreDocumentGutter('left');
        $this->assertEquals('left', $this->componentLayout->getIgnoreDocumentGutter());

        $this->componentLayout->setIgnoreDocumentGutter('right');
        $this->assertEquals('right', $this->componentLayout->getIgnoreDocumentGutter());

        $this->componentLayout->setIgnoreDocumentGutter('both');
        $this->assertEquals('both', $this->componentLayout->getIgnoreDocumentGutter());

        $this->componentLayout->setIgnoreDocumentGutter('invliad');
    }

    public function testSetIgnoreDocumentMargin(): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->assertNull($this->componentLayout->getIgnoreDocumentMargin());

        $this->componentLayout->setIgnoreDocumentMargin();
        $this->assertNull($this->componentLayout->getIgnoreDocumentMargin());

        $this->componentLayout->setIgnoreDocumentMargin(true);
        $this->assertTrue($this->componentLayout->getIgnoreDocumentMargin());

        $this->componentLayout->setIgnoreDocumentMargin(false);
        $this->assertFalse($this->componentLayout->getIgnoreDocumentMargin());

        $this->componentLayout->setIgnoreDocumentMargin('none');
        $this->assertEquals('none', $this->componentLayout->getIgnoreDocumentMargin());

        $this->componentLayout->setIgnoreDocumentMargin('left');
        $this->assertEquals('left', $this->componentLayout->getIgnoreDocumentMargin());

        $this->componentLayout->setIgnoreDocumentMargin('right');
        $this->assertEquals('right', $this->componentLayout->getIgnoreDocumentMargin());

        $this->componentLayout->setIgnoreDocumentMargin('both');
        $this->assertEquals('both', $this->componentLayout->getIgnoreDocumentMargin());

        $this->componentLayout->setIgnoreDocumentMargin('invliad');
    }

    public function testIgnoreViewportPadding(): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->assertNull($this->componentLayout->getIgnoreViewportPadding());

        $this->componentLayout->setIgnoreViewportPadding();
        $this->assertSame('none', $this->componentLayout->getIgnoreViewportPadding());

        $this->componentLayout->setIgnoreViewportPadding(true);
        $this->assertTrue($this->componentLayout->getIgnoreViewportPadding());

        $this->componentLayout->setIgnoreViewportPadding(false);
        $this->assertFalse($this->componentLayout->getIgnoreViewportPadding());

        $this->componentLayout->setIgnoreViewportPadding('none');
        $this->assertEquals('none', $this->componentLayout->getIgnoreViewportPadding());

        $this->componentLayout->setIgnoreViewportPadding('left');
        $this->assertEquals('left', $this->componentLayout->getIgnoreViewportPadding());

        $this->componentLayout->setIgnoreViewportPadding('right');
        $this->assertEquals('right', $this->componentLayout->getIgnoreViewportPadding());

        $this->componentLayout->setIgnoreViewportPadding('both');
        $this->assertEquals('both', $this->componentLayout->getIgnoreViewportPadding());

        $this->componentLayout->setIgnoreViewportPadding('invalid');
    }

    public function testSetMaximumContentWidth(): void
    {
        $this->componentLayout->setMaximumContentWidth(10);
        $this->assertEquals(10, $this->componentLayout->getMaximumContentWidth());
    }

    public function testSetMargin(): void
    {
        $this->componentLayout->setMargin(2);
        $this->assertEquals(2, $this->componentLayout->getMargin());

        $margin = new Margin();
        $top = new SupportedUnits('4vh');
        $margin->setBottom(3)->setTop($top);

        $this->componentLayout->setMargin($margin);
        $this->assertSame($margin, $this->componentLayout->getMargin());
    }

    public function testJsonSerialize(): void
    {
        $expected = [
            'columnStart' => 0,
            'columnSpan'  => 3,
            'margin'      => new Margin(50),
        ];

        $this->componentLayout
            ->setColumnStart(0)
            ->setColumnSpan(3)
            ->setMargin(new Margin(50));

        $this->assertJsonStringEqualsJsonString(json_encode($expected), json_encode($this->componentLayout));
    }
}
