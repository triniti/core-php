<?php
declare(strict_types=1);

namespace Triniti\Tests\Notify;

use Acme\Schemas\Canvas\Node\PageV1;
use Acme\Schemas\Iam\Node\AndroidAppV1;
use Acme\Schemas\News\Command\CreateArticleV1;
use Acme\Schemas\News\Node\ArticleV1;
use Acme\Schemas\Notify\Command\CreateNotificationV1;
use Acme\Schemas\Notify\Command\UpdateNotificationV1;
use Acme\Schemas\Notify\Node\AndroidNotificationV1;
use Acme\Schemas\Notify\Node\BrowserNotificationV1;
use Acme\Schemas\Notify\Node\EmailNotificationV1;
use Acme\Schemas\Notify\Node\IosNotificationV1;
use Gdbots\Schemas\Ncr\Enum\NodeStatus;
use Triniti\News\ArticleAggregate;
use Triniti\Notify\Exception\InvalidNotificationContent;
use Triniti\Notify\NotificationAggregate;
use Triniti\Schemas\Notify\Enum\NotificationSendStatus;
use Triniti\Tests\AbstractPbjxTest;

final class NotificationAggregateTest extends AbstractPbjxTest
{
    public function testCreateNotification(): void
    {
        $article = ArticleV1::create();
        $articleRef = $article->generateNodeRef();

        $notification = IosNotificationV1::create()
            ->set('content_ref', $articleRef)
            ->set('sent_at', new \DateTime('2 years ago'));
        $notificationAggregate = NotificationAggregate::fromNode($notification, $this->pbjx);
        $notificationAggregate->createNode(CreateNotificationV1::create()->set('node', $notification));
        $aggregateNode = $notificationAggregate->getUncommittedEvents()[0]->get('node');

        $this->assertTrue(NodeStatus::PUBLISHED()->equals($aggregateNode->get('status')));
        $this->assertTrue(NotificationSendStatus::DRAFT()->equals($aggregateNode->get('send_status')));
        $this->assertFalse($aggregateNode->has('sent_at'));
    }

    public function testCreateNotificationContentDoesntHaveNotifications(): void
    {
        $page = PageV1::create();
        $notification = BrowserNotificationV1::create()
            ->set('content_ref', $page->generateNodeRef())
            ->set('send_on_publish', true)
            ->set('sent_at', new \DateTime('2 years ago'));
        $aggregate = NotificationAggregate::fromNode($notification, $this->pbjx);
        $this->expectException(InvalidNotificationContent::class);
        $aggregate->createNode(CreateNotificationV1::create()->set('node', $notification));
    }

    public function testCreateNotificationSendOnPublish(): void
    {
        $publishedAt = new \DateTime('2021-01-01T15:03:01.012345Z');
        $article = ArticleV1::create()
            ->set('title', 'foo')
            ->set('published_at', $publishedAt);
        $articleAggregate = ArticleAggregate::fromNode($article, $this->pbjx);
        $articleAggregate->createNode(CreateArticleV1::create()->set('node', $article));
        $articleAggregate->commit();
        $articleRef = $article->generateNodeRef();

        $notification = EmailNotificationV1::create()
            ->set('content_ref', $articleRef)
            ->set('send_on_publish', true);
        $notificationAggregate = NotificationAggregate::fromNode($notification, $this->pbjx);
        $notificationAggregate->createNode(CreateNotificationV1::create()->set('node', $notification));
        $aggregateNode = $notificationAggregate->getUncommittedEvents()[0]->get('node');

        $this->assertTrue(NodeStatus::PUBLISHED()->equals($aggregateNode->get('status')));
        $this->assertFalse($aggregateNode->has('sent_at'));
        $this->assertTrue(NotificationSendStatus::SCHEDULED()->equals($aggregateNode->get('send_status')));
        $this->assertSame('foo', $aggregateNode->get('title'));
        $this->assertEquals($publishedAt->add(\DateInterval::createFromDateString('10 seconds'))->getTimestamp(), $aggregateNode->get('send_at')->getTimestamp());
    }

    public function testCreateNotificationAlreadySent(): void
    {
        $publishedAt = new \DateTime('2021-01-01T15:03:01.012345Z');
        $article = ArticleV1::create()
            ->set('title', 'foo')
            ->set('published_at', $publishedAt);
        $articleAggregate = ArticleAggregate::fromNode($article, $this->pbjx);
        $articleAggregate->createNode(CreateArticleV1::create()->set('node', $article));
        $articleAggregate->commit();
        $articleRef = $article->generateNodeRef();

        $notification = EmailNotificationV1::create()
            ->set('title', 'bar')
            ->set('content_ref', $articleRef)
            ->set('send_on_publish', true)
            ->set('send_status', NotificationSendStatus::SENT());
        $notificationAggregate = NotificationAggregate::fromNode($notification, $this->pbjx);
        $notificationAggregate->createNode(CreateNotificationV1::create()->set('node', $notification));
        $aggregateNode = $notificationAggregate->getUncommittedEvents()[0]->get('node');

        $this->assertTrue(NodeStatus::PUBLISHED()->equals($aggregateNode->get('status')));
        $this->assertTrue(NotificationSendStatus::SENT()->equals($aggregateNode->get('send_status')));
        $this->assertFalse($aggregateNode->has('sent_at'));
        $this->assertSame('bar', $aggregateNode->get('title'));
        $this->assertFalse($aggregateNode->has('send_at'));
    }

    public function testUpdateNotification(): void
    {
        $oldApp = AndroidAppV1::create();
        $oldContent = ArticleV1::create();
        $oldStatus = NotificationSendStatus::SCHEDULED();
        $oldSentAt = new \DateTime('2021-01-01T15:03:01.012345Z');
        $oldNode = AndroidNotificationV1::create()
            ->set('app_ref', $oldApp->generateNodeRef())
            ->set('content_ref', $oldContent->generateNodeRef())
            ->set('send_status', $oldStatus)
            ->set('sent_at', $oldSentAt);

        $newApp = AndroidAppV1::create();
        $newContent = ArticleV1::create();
        $newStatus = NotificationSendStatus::DRAFT();
        $newSentAt = new \DateTime('2031-01-01T15:03:01.012345Z');
        $newNode = (clone $oldNode)
            ->set('app_ref', $newApp->generateNodeRef())
            ->set('content_ref', $newContent->generateNodeRef())
            ->set('send_status', $newStatus)
            ->set('sent_at', $newSentAt)
            ->set('status', NodeStatus::DRAFT());

        $aggregate = NotificationAggregate::fromNode($oldNode, $this->pbjx);
        $command = UpdateNotificationV1::create()
            ->set('node_ref', $oldNode->generateNodeRef())
            ->set('old_node', $oldNode)
            ->set('new_node', $newNode);
        $aggregate->updateNode($command);

        $aggregateNode = $aggregate->getUncommittedEvents()[0]->get('new_node');
        $this->assertFalse($newApp->generateNodeRef()->equals($aggregateNode->get('app_ref')));
        $this->assertFalse($newContent->generateNodeRef()->equals($aggregateNode->get('content_ref')));
        $this->assertFalse($newStatus->equals($aggregateNode->get('send_status')));
        $this->assertFalse($newSentAt->getTimestamp() === $aggregateNode->get('sent_at')->getTimeStamp());
        $this->assertFalse(NodeStatus::DRAFT()->equals($aggregateNode->get('status')));
    }
}
