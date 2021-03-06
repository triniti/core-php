<?php

namespace Triniti\Tests\AppleNews\Layout;

use Assert\AssertionFailedException;
use PHPUnit\Framework\TestCase;
use Triniti\AppleNews\Condition;
use Triniti\AppleNews\Layout\ConditionalComponentLayout;

class ConditionalComponentLayoutTest extends TestCase
{
    protected ConditionalComponentLayout $conditionalComponentLayout;

    public function setUp(): void
    {
        $this->conditionalComponentLayout = new ConditionalComponentLayout();
    }

    public function testCreateConditionalComponentLayout(): void
    {
        $this->assertInstanceOf(
            'Triniti\AppleNews\Layout\ConditionalComponentLayout',
            $this->conditionalComponentLayout
        );
    }

    public function testGetSetAddCondition(): void
    {
        $condition1 = new Condition();
        $condition1->setPlatform('any');

        $condition2 = new Condition();
        $condition2->setMaxColumns(1);

        $conditions = [$condition1];

        $this->conditionalComponentLayout->setConditions($conditions);
        $this->assertEquals($conditions, $this->conditionalComponentLayout->getConditions());

        $conditions[] = $condition2;
        $this->conditionalComponentLayout->addConditions($conditions);
        $this->assertEquals([$condition1, $condition1, $condition2], $this->conditionalComponentLayout->getConditions());

        $this->conditionalComponentLayout->addConditions();
        $this->assertEquals([$condition1, $condition1, $condition2], $this->conditionalComponentLayout->getConditions());

        $this->conditionalComponentLayout->addConditions(null);
        $this->assertEquals([$condition1, $condition1, $condition2], $this->conditionalComponentLayout->getConditions());

        $this->conditionalComponentLayout->setConditions();
        $this->assertEquals([], $this->conditionalComponentLayout->getConditions());

        $this->conditionalComponentLayout->setConditions(null);
        $this->assertEquals([], $this->conditionalComponentLayout->getConditions());
    }

    public function testValidate(): void
    {
        $this->expectException(AssertionFailedException::class);
        $conditionalComponentLayout = new ConditionalComponentLayout();
        $conditionalComponentLayout->validate();
    }

    public function testJsonSerialize(): void
    {
        $conditionalComponentLayout = new ConditionalComponentLayout();
        $condition = new Condition();
        $condition->setMaxColumns(1);
        $conditionalComponentLayout->addCondition($condition);

        $expected = [
            'conditions' => [$condition],
        ];


        $this->assertJsonStringEqualsJsonString(json_encode($expected), json_encode($conditionalComponentLayout));
    }
}
