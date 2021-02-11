<?php

namespace Triniti\Tests\AppleNews\Layout;

use PHPUnit\Framework\TestCase;
use Triniti\AppleNews\CollectionDisplay;
use Triniti\AppleNews\Component\Caption;
use Triniti\AppleNews\Component\Container;
use Triniti\AppleNews\Component\Title;
use Triniti\AppleNews\Link\ComponentLink;

class ContainerTest extends TestCase
{
    protected Container $container;

    public function setUp(): void
    {
        $this->container = new Container();
    }

    public function testCreateContainer(): void
    {
        $this->assertInstanceOf(
            'Triniti\AppleNews\Component\Container',
            $this->container
        );
    }

    public function testGetSetAddAddition(): void
    {
        $link = new ComponentLink();
        $link->setURL('http://test.com');

        $link2 = new ComponentLink();
        $link2->setURL('http://test2.com');
        $additions[] = $link;

        $this->container->setAdditions($additions);
        $this->assertEquals($additions, $this->container->getAdditions());

        $additions[] = $link2;
        $this->container->addAdditions($additions);
        $this->assertEquals([$link, $link, $link2], $this->container->getAdditions());

        $this->container->addAdditions();
        $this->assertEquals([$link, $link, $link2], $this->container->getAdditions());

        $this->container->addAdditions(null);
        $this->assertEquals([$link, $link, $link2], $this->container->getAdditions());

        $this->container->setAdditions();
        $this->assertEquals([], $this->container->getAdditions());

        $this->container->setAdditions(null);
        $this->assertEquals([], $this->container->getAdditions());
    }

    public function testGetSetAddComponent(): void
    {
        $component = new Caption();
        $component->setText('test');

        $component2 = new Title();
        $component2->setText('test2');

        $components[] = $component;

        $this->container->setComponents($components);
        $this->assertEquals($components, $this->container->getComponents());

        $components[] = $component2;
        $this->container->addComponents($components);
        $this->assertEquals([$component, $component, $component2], $this->container->getComponents());

        $this->container->addComponents();
        $this->assertEquals([$component, $component, $component2], $this->container->getComponents());

        $this->container->addComponents(null);
        $this->assertEquals([$component, $component, $component2], $this->container->getComponents());

        $this->container->setComponents();
        $this->assertEquals([], $this->container->getComponents());

        $this->container->setComponents(null);
        $this->assertEquals([], $this->container->getComponents());
    }

    public function testGetSetContentDisplay(): void
    {
        $display = new CollectionDisplay();

        $this->container->setContentDisplay($display);
        $this->assertEquals($display, $this->container->getContentDisplay());

        $this->container->setContentDisplay();
        $this->assertNull($this->container->getContentDisplay());
    }

    public function testJsonSerialize(): void
    {
        $link = new ComponentLink();
        $link->setURL('http://test.com');

        $component = new Caption();
        $component->setText('test');

        $expected = [
            'role'           => 'container',
            'additions'      => [$link],
            'components'     => [$component],
            'contentDisplay' => new CollectionDisplay(),
        ];

        $this->container
            ->setContentDisplay(new CollectionDisplay())
            ->setAdditions([$link])
            ->setComponents([$component]);

        $this->assertJsonStringEqualsJsonString(json_encode($expected), json_encode($this->container));
    }
}
