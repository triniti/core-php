<?php
declare(strict_types=1);

namespace Triniti\Tests\Notify;

use Acme\Schemas\Notify\Event\NotificationCreatedV1;
use Acme\Schemas\Notify\Event\NotificationDeletedV1;
use Acme\Schemas\Notify\Event\NotificationUpdatedV1;
use Acme\Schemas\Notify\Node\IosNotificationV1;
use Triniti\Notify\NotificationWatcher;
use Triniti\Schemas\Notify\Command\SendNotificationV1;
use Triniti\Schemas\Notify\Enum\NotificationSendStatus;
use Triniti\Tests\AbstractPbjxTest;
use Triniti\Tests\MockPbjx;

final class NotificationWatcherTest extends AbstractPbjxTest
{
    private NotificationWatcher $watcher;

    protected function setup(): void
    {
        parent::setup();
        $this->pbjx = new MockPbjx($this->locator);
        $this->watcher = new NotificationWatcher();
    }

    public function testOnNotificationCreated(): void
    {
        $sendAt = new \DateTime('2031-01-01T15:03:01.012345Z');
        $node = IosNotificationV1::create()
            ->set('send_at',$sendAt)
            ->set('send_status', NotificationSendStatus::SCHEDULED());
        $this->watcher->onNotificationCreated(NotificationCreatedV1::create()->set('node', $node), $this->pbjx);
        $sentCommand = $this->pbjx->getSent()[0]['command'];
        $this->assertInstanceOf(SendNotificationV1::class, $sentCommand);
        $this->assertTrue($node->generateNodeRef()->equals($sentCommand->get('node_ref')));
        $this->assertSame($sendAt->getTimestamp(), $this->pbjx->getSent()[0]['timestamp']);
    }

    public function testOnNotificationCreatedIsReplay(): void
    {
        $sendAt = new \DateTime('2031-01-01T15:03:01.012345Z');
        $node = IosNotificationV1::create()
            ->set('send_at',$sendAt)
            ->set('send_status', NotificationSendStatus::SCHEDULED());
        $event = NotificationCreatedV1::create()->set('node', $node);
        $event->isReplay(true);
        $this->watcher->onNotificationCreated($event, $this->pbjx);
        $this->assertEmpty($this->pbjx->getSent());
    }

    public function testOnNotificationCreatedNoRequiredFields(): void
    {
        $node = IosNotificationV1::create();
        $this->watcher->onNotificationCreated(NotificationCreatedV1::create()->set('node', $node), $this->pbjx);
        $this->assertEmpty($this->pbjx->getSent());

        $node->set('send_at', new \DateTime('2031-01-01T15:03:01.012345Z'));
        $this->watcher->onNotificationCreated(NotificationCreatedV1::create()->set('node', $node), $this->pbjx);
        $this->assertEmpty($this->pbjx->getSent());

        $node->set('send_status', NotificationSendStatus::DRAFT());
        $this->watcher->onNotificationCreated(NotificationCreatedV1::create()->set('node', $node), $this->pbjx);
        $this->assertEmpty($this->pbjx->getSent());
    }

    public function testOnNotificationDeleted(): void
    {
        $node = IosNotificationV1::create();
        $this->assertFalse($this->pbjx->getCanceled());
        $this->watcher->onNotificationDeleted(NotificationDeletedV1::create()->set('node_ref', $node->generateNodeRef()), $this->pbjx);
        $this->assertTrue($this->pbjx->getCanceled());
    }

    public function testOnNotificationDeletedIsReplay(): void
    {
        $node = IosNotificationV1::create();
        $this->assertFalse($this->pbjx->getCanceled());
        $event = NotificationDeletedV1::create()->set('node_ref', $node->generateNodeRef());
        $event->isReplay(true);
        $this->watcher->onNotificationDeleted($event, $this->pbjx);
        $this->assertFalse($this->pbjx->getCanceled());
    }

    public function testOnNotificationUpdated(): void
    {
        $oldNode = IosNotificationV1::create()
            ->set('send_at', new \DateTime('2031-01-01T15:03:01.012345Z'))
            ->set('send_status', NotificationSendStatus::SCHEDULED());
        $newSendAt = new \DateTime('2041-01-01T15:03:01.012345Z');
        $newNode = (clone $oldNode)->set('send_at', $newSendAt);
        $event = NotificationUpdatedV1::create()
            ->set('node_ref', $oldNode->generateNodeRef())
            ->set('old_node', $oldNode)
            ->set('new_node', $newNode);
        $this->watcher->onNotificationUpdated($event, $this->pbjx);
        $sentCommand = $this->pbjx->getSent()[0]['command'];
        $this->assertInstanceOf(SendNotificationV1::class, $sentCommand);
        $this->assertTrue($oldNode->generateNodeRef()->equals($sentCommand->get('node_ref')));
        $this->assertSame($newSendAt->getTimestamp(), $this->pbjx->getSent()[0]['timestamp']);
    }

    public function testOnNotificationUpdatedIsReplay(): void
    {
        $oldNode = IosNotificationV1::create()
            ->set('send_at', new \DateTime('2031-01-01T15:03:01.012345Z'))
            ->set('send_status', NotificationSendStatus::SCHEDULED());
        $newSendAt = new \DateTime('2041-01-01T15:03:01.012345Z');
        $newNode = (clone $oldNode)->set('send_at', $newSendAt);
        $event = NotificationUpdatedV1::create()
            ->set('node_ref', $oldNode->generateNodeRef())
            ->set('old_node', $oldNode)
            ->set('new_node', $newNode);
        $event->isReplay(true);
        $this->watcher->onNotificationUpdated($event, $this->pbjx);
        $this->assertEmpty($this->pbjx->getSent());
    }

    public function testOnNotificationUpdatedSameSendAt(): void
    {
        $sendAt = new \DateTime('2031-01-01T15:03:01.012345Z');
        $oldNode = IosNotificationV1::create()
            ->set('send_at', $sendAt)
            ->set('send_status', NotificationSendStatus::SCHEDULED());
        $newNode = (clone $oldNode)->set('send_at', $sendAt);
        $event = NotificationUpdatedV1::create()
            ->set('old_node', $oldNode)
            ->set('new_node', $newNode);
        $this->watcher->onNotificationUpdated($event, $this->pbjx);
        $this->assertEmpty($this->pbjx->getSent());
    }

    public function testOnNotificationUpdatedNoSendAt(): void
    {
        $oldNode = IosNotificationV1::create()
            ->set('send_status', NotificationSendStatus::SCHEDULED());
        $newNode = clone $oldNode;
        $event = NotificationUpdatedV1::create()
            ->set('old_node', $oldNode)
            ->set('new_node', $newNode);
        $this->watcher->onNotificationUpdated($event, $this->pbjx);
        $this->assertEmpty($this->pbjx->getSent());
    }

    public function testOnNotificationUpdatedOnlyOldSendAt(): void
    {
        $oldNode = IosNotificationV1::create()
            ->set('send_at', new \DateTime('2031-01-01T15:03:01.012345Z'))
            ->set('send_status', NotificationSendStatus::SCHEDULED());
        $newNode = (clone $oldNode)->clear('send_at');
        $event = NotificationUpdatedV1::create()
            ->set('old_node', $oldNode)
            ->set('new_node', $newNode);
        $this->watcher->onNotificationUpdated($event, $this->pbjx);
        $this->assertEmpty($this->pbjx->getSent());
        $this->assertTrue($this->pbjx->getCanceled());
    }
}
