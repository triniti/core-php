<?php
declare(strict_types=1);

namespace Triniti\Tests\Notify;

use Acme\Schemas\Iam\Node\IosAppV1;
use Acme\Schemas\News\Command\PublishArticleV1;
use Acme\Schemas\News\Node\ArticleV1;
use Acme\Schemas\Notify\Command\CreateNotificationV1;
use Acme\Schemas\Notify\Command\SendNotificationV1;
use Acme\Schemas\Notify\Node\IosNotificationV1;
use Gdbots\Schemas\Pbjx\StreamId;
use Triniti\News\ArticleAggregate;
use Triniti\Notify\NotificationAggregate;
use Triniti\Notify\SendNotificationHandler;
use Triniti\Schemas\Notify\Event\NotificationFailedV1;
use Triniti\Schemas\Notify\Event\NotificationSentV1;
use Triniti\Tests\AbstractPbjxTest;
use Triniti\Tests\MockPbjx;

final class SendNotificationHandlerTest extends AbstractPbjxTest
{
    protected function setup(): void
    {
        parent::setup();
        $this->pbjx = new MockPbjx($this->locator);
    }

    public function testSendNotification(): void
    {
        $content = ArticleV1::create();
        $contentRef = $content->generateNodeRef();
        $contentAggregate = ArticleAggregate::fromNodeRef($contentRef, $this->pbjx);
        $contentAggregate->publishNode(PublishArticleV1::create()->set('node_ref', $contentRef));
        $contentAggregate->commit();

        $app = IosAppV1::create();
        $notification = IosNotificationV1::create()
            ->set('content_ref', $content->generateNodeRef())
            ->set('app_ref', $app->generateNodeRef())
            ->set('send_at', new \DateTime('NOW'));
        $notificationRef = $notification->generateNodeRef();
        $notificationAggregate = NotificationAggregate::fromNodeRef($notificationRef, $this->pbjx);
        $notificationAggregate->createNode(CreateNotificationV1::create()->set('node', $notification));
        $notificationAggregate->commit();

        $handler = new SendNotificationHandler(new MockNotifierLocator());
        $command = SendNotificationV1::create()->set('node_ref', $notificationRef);
        $handler->handleCommand($command, $this->pbjx);
        $sentCount = 0;
        foreach ($this->eventStore->pipeEvents(StreamId::fromNodeRef($notificationRef)) as $event) {
            if ($event instanceof NotificationSentV1) {
                $sentCount++;
            }
        }
        $this->assertSame(1, $sentCount);
    }

    public function testFailNotification(): void
    {
        $content = ArticleV1::create();
        $contentRef = $content->generateNodeRef();
        $contentAggregate = ArticleAggregate::fromNodeRef($contentRef, $this->pbjx);
        $contentAggregate->publishNode(PublishArticleV1::create()->set('node_ref', $contentRef));
        $contentAggregate->commit();

        $app = IosAppV1::create();
        $notification = IosNotificationV1::create()
            ->set('content_ref', $content->generateNodeRef())
            ->set('app_ref', $app->generateNodeRef())
            ->set('send_at', new \DateTime('NOW'));
        $notificationRef = $notification->generateNodeRef();
        $notificationAggregate = NotificationAggregate::fromNodeRef($notificationRef, $this->pbjx);
        $notificationAggregate->createNode(CreateNotificationV1::create()->set('node', $notification));
        $notificationAggregate->commit();

        $handler = new SendNotificationHandler(new MockNotifierLocator(false));
        $command = SendNotificationV1::create()->set('node_ref', $notificationRef);
        $handler->handleCommand($command, $this->pbjx);
        $sentCount = 0;
        foreach ($this->eventStore->pipeEvents(StreamId::fromNodeRef($notificationRef)) as $event) {
            if ($event instanceof NotificationFailedV1) {
                $sentCount++;
            }
        }
        $this->assertSame(1, $sentCount);
    }

    public function testRetryNotification(): void
    {
        $content = ArticleV1::create();
        $contentRef = $content->generateNodeRef();
        $contentAggregate = ArticleAggregate::fromNodeRef($contentRef, $this->pbjx);
        $contentAggregate->publishNode(PublishArticleV1::create()->set('node_ref', $contentRef));
        $contentAggregate->commit();

        $app = IosAppV1::create();
        $notification = IosNotificationV1::create()
            ->set('content_ref', $content->generateNodeRef())
            ->set('app_ref', $app->generateNodeRef())
            ->set('send_at', new \DateTime('NOW'));
        $notificationRef = $notification->generateNodeRef();
        $notificationAggregate = NotificationAggregate::fromNodeRef($notificationRef, $this->pbjx);
        $notificationAggregate->createNode(CreateNotificationV1::create()->set('node', $notification));
        $notificationAggregate->commit();

        $handler = new SendNotificationHandler(new MockNotifierLocator(false, true));
        $command = SendNotificationV1::create()->set('node_ref', $notificationRef);
        $handler->handleCommand($command, $this->pbjx);

        $sentCommand = $this->pbjx->getSent()[0]['command'];
        $this->assertInstanceOf(SendNotificationV1::class, $sentCommand);
        $this->assertSame(1, $sentCommand->get('ctx_retries'));
        $this->assertTrue($notificationRef->equals($sentCommand->get('node_ref')));
    }
}
