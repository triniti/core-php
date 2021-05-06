<?php

namespace Triniti\Tests\AppleNews\Style;

use Assert\AssertionFailedException;
use Triniti\AppleNews\Condition;
use Triniti\AppleNews\Style\ConditionalComponentStyle;
use Triniti\Tests\AbstractPbjxTest;

class ConditionalComponentStyleTest extends AbstractPbjxTest
{
    /** @var ConditionalComponentStyle */
    protected $conditionalComponentStyle;

    public function setup(): void
    {
        $this->conditionalComponentStyle = new ConditionalComponentStyle();
    }

    /**
     * @test testCreateConditionalComponentStyle
     */
    public function testCreateConditionalComponentStyle()
    {
        $this->assertInstanceOf(
            'Triniti\AppleNews\Style\ConditionalComponentStyle',
            $this->conditionalComponentStyle
        );
    }

    /**
     * @test testGetSetAddCondition
     */
    public function testGetSetAddCondition()
    {
        $condition1 = new Condition();
        $condition1->setPlatform('any');

        $condition2 = new Condition();
        $condition2->setMaxColumns(1);

        $conditions = [$condition1];

        $this->conditionalComponentStyle->setConditions($conditions);
        $this->assertEquals($conditions, $this->conditionalComponentStyle->getConditions());

        $conditions[] = $condition2;
        $this->conditionalComponentStyle->addConditions($conditions);
        $this->assertEquals([$condition1, $condition1, $condition2], $this->conditionalComponentStyle->getConditions());

        $this->conditionalComponentStyle->addConditions();
        $this->assertEquals([$condition1, $condition1, $condition2], $this->conditionalComponentStyle->getConditions());

        $this->conditionalComponentStyle->addConditions(null);
        $this->assertEquals([$condition1, $condition1, $condition2], $this->conditionalComponentStyle->getConditions());

        $this->conditionalComponentStyle->setConditions();
        $this->assertEquals([], $this->conditionalComponentStyle->getConditions());

        $this->conditionalComponentStyle->setConditions(null);
        $this->assertEquals([], $this->conditionalComponentStyle->getConditions());
    }

    /**
     * @test testValidate
     */
    public function testValidate()
    {
        $this->expectException(AssertionFailedException::class);
        $conditionalComponentLayout = new ConditionalComponentStyle();
        $conditionalComponentLayout->validate();
    }

    /**
     * @test testJsonSerialize
     */
    public function testJsonSerialize()
    {
        $conditionalComponentStyle = new ConditionalComponentStyle();
        $condition = new Condition();
        $condition->setMaxColumns(1);
        $conditionalComponentStyle->addCondition($condition);

        $expected = [
            'conditions' => [$condition],
            'opacity'    => 1,
        ];


        $this->assertJsonStringEqualsJsonString(json_encode($expected), json_encode($conditionalComponentStyle));
    }
}
