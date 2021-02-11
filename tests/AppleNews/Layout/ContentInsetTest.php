<?php
/**
 * Created by PhpStorm.
 * User: wei
 * Date: 7/23/18
 * Time: 3:46 PM
 */

namespace Triniti\Tests\AppleNews\Layout;

use PHPUnit\Framework\TestCase;
use Triniti\AppleNews\Layout\ContentInset;

class ContentInsetTest extends TestCase
{
    protected ContentInset $contentInset;

    protected function setup(): void
    {
        $this->contentInset = new ContentInset();
    }

    public function testCreateContentInset(): void
    {
        $this->assertInstanceOf(
            'Triniti\AppleNews\Layout\ContentInset',
            $this->contentInset
        );

        $this->assertNull($this->contentInset->getBottom());
        $this->assertNull($this->contentInset->getLeft());
        $this->assertNull($this->contentInset->getTop());
        $this->assertNull($this->contentInset->getRight());
    }

    public function testSetters(): void
    {
        $this->contentInset
            ->setBottom(false)
            ->setRight()
            ->setLeft(true);

        $this->assertTrue($this->contentInset->getLeft());
        $this->assertTrue($this->contentInset->getRight());
        $this->assertFalse($this->contentInset->getBottom());
        $this->assertNull($this->contentInset->getTop());

        $this->contentInset->setTop();
        $this->assertTrue($this->contentInset->getTop());
    }

    public function testJsonSerialize(): void
    {
        $expected = [
            'bottom' => true,
            'left'   => true,
            'top'    => false,
        ];

        $this->contentInset
            ->setBottom()
            ->setLeft()
            ->setTop(false);

        $this->assertJsonStringEqualsJsonString(json_encode($expected), json_encode($this->contentInset));
    }
}
