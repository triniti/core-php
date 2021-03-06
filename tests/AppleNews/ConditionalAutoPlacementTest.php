<?php

namespace Triniti\Tests\AppleNews\Layout;

use Assert\AssertionFailedException;
use PHPUnit\Framework\TestCase;
use Triniti\AppleNews\Condition;
use Triniti\AppleNews\Layout\AutoPlacementLayout;
use Triniti\AppleNews\ConditionalAutoPlacement;
use Triniti\Tests\AppleNews\AbstractPbjxTest;

class ConditionalAutoPlacementTest extends TestCase
{
    protected ConditionalAutoPlacement $conditionalAutoPlacement;

    public function setUp(): void
    {
        $this->conditionalAutoPlacement = new ConditionalAutoPlacement();
    }

    public function testCreateConditionalAutoPlacement(): void
    {
        $this->assertInstanceOf(
            'Triniti\AppleNews\ConditionalAutoPlacement',
            $this->conditionalAutoPlacement
        );
    }

    public function testGetSetAddCondition(): void
    {
        $condition1 = new Condition();
        $condition1->setPlatform('any');

        $condition2 = new Condition();
        $condition2->setMaxColumns(1);

        $conditions = [$condition1];

        $this->conditionalAutoPlacement->setConditions($conditions);
        $this->assertEquals($conditions, $this->conditionalAutoPlacement->getConditions());

        $conditions[] = $condition2;
        $this->conditionalAutoPlacement->addConditions($conditions);
        $this->assertEquals([$condition1, $condition1, $condition2], $this->conditionalAutoPlacement->getConditions());

        $this->conditionalAutoPlacement->addConditions();
        $this->assertEquals([$condition1, $condition1, $condition2], $this->conditionalAutoPlacement->getConditions());

        $this->conditionalAutoPlacement->addConditions(null);
        $this->assertEquals([$condition1, $condition1, $condition2], $this->conditionalAutoPlacement->getConditions());

        $this->conditionalAutoPlacement->setConditions();
        $this->assertEquals([], $this->conditionalAutoPlacement->getConditions());

        $this->conditionalAutoPlacement->setConditions(null);
        $this->assertEquals([], $this->conditionalAutoPlacement->getConditions());
    }

    public function testGetSetEnabled(): void
    {
        $this->conditionalAutoPlacement->setEnabled(true);
        $this->assertTrue($this->conditionalAutoPlacement->getEnabled());
    }

    public function testGetSetLayout(): void
    {
        $this->assertNull($this->conditionalAutoPlacement->getLayout());
        $layout = new AutoPlacementLayout();
        $this->conditionalAutoPlacement->setLayout($layout);

        $this->assertSame($layout, $this->conditionalAutoPlacement->getLayout());
    }

    public function testValidate(): void
    {
        $this->expectException(AssertionFailedException::class);
        $conditionalAutoPlacement = new conditionalAutoPlacement();
        $conditionalAutoPlacement->validate();
    }

    public function testJsonSerialize(): void
    {
        $conditionalAutoPlacement = new conditionalAutoPlacement();
        $condition = new Condition();
        $condition->setMaxColumns(1);
        $conditionalAutoPlacement->addCondition($condition);
        $layout = new AutoPlacementLayout();
        $layout->setMargin(1);
        $conditionalAutoPlacement->setLayout($layout);

        $expected = [
            'conditions' => [$condition],
            'enabled'    => false,
            'layout'     => $layout,
        ];

        $this->assertJsonStringEqualsJsonString(json_encode($expected), json_encode($conditionalAutoPlacement));
    }
}
