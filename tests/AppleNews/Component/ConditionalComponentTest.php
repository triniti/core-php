<?php

namespace Triniti\Tests\AppleNews\Layout;

use Assert\AssertionFailedException;
use PHPUnit\Framework\TestCase;
use Triniti\AppleNews\Condition;
use Triniti\AppleNews\Component\ConditionalComponent;

class ConditionalComponentTest extends TestCase
{
    protected ConditionalComponent $conditionalComponent;

    public function setUp(): void
    {
        $this->conditionalComponent = new ConditionalComponent();
    }

    public function testCreateConditionalComponentLayout(): void
    {
        $this->assertInstanceOf(
            'Triniti\AppleNews\Component\ConditionalComponent',
            $this->conditionalComponent
        );
    }

    public function testGetSetAddCondition(): void
    {
        $condition1 = new Condition();
        $condition1->setPlatform('any');

        $condition2 = new Condition();
        $condition2->setMaxColumns(1);

        $conditions = [$condition1];

        $this->conditionalComponent->setConditions($conditions);
        $this->assertEquals($conditions, $this->conditionalComponent->getConditions());

        $conditions[] = $condition2;
        $this->conditionalComponent->addConditions($conditions);
        $this->assertEquals([$condition1, $condition1, $condition2], $this->conditionalComponent->getConditions());

        $this->conditionalComponent->addConditions();
        $this->assertEquals([$condition1, $condition1, $condition2], $this->conditionalComponent->getConditions());

        $this->conditionalComponent->addConditions(null);
        $this->assertEquals([$condition1, $condition1, $condition2], $this->conditionalComponent->getConditions());

        $this->conditionalComponent->setConditions();
        $this->assertEquals([], $this->conditionalComponent->getConditions());

        $this->conditionalComponent->setConditions(null);
        $this->assertEquals([], $this->conditionalComponent->getConditions());
    }

    public function testValidate(): void
    {
        $this->expectException(AssertionFailedException::class);
        $conditionalComponent = new ConditionalComponent();
        $conditionalComponent->validate();
    }

    public function testJsonSerialize(): void
    {
        $conditionalComponent = new ConditionalComponent();
        $condition = new Condition();
        $condition->setMaxColumns(1);
        $conditionalComponent->addCondition($condition);

        $expected = [
            'conditions' => [$condition],
        ];


        $this->assertJsonStringEqualsJsonString(json_encode($expected), json_encode($conditionalComponent));
    }
}
