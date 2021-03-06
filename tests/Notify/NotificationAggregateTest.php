<?php
declare(strict_types=1);

namespace Triniti\Tests\Notify;

use Acme\Schemas\Canvas\Node\PageV1;
use Acme\Schemas\News\Command\CreateArticleV1;
use Acme\Schemas\News\Node\ArticleV1;
use Acme\Schemas\Notify\Command\CreateNotificationV1;
use Acme\Schemas\Notify\Node\BrowserNotificationV1;
use Acme\Schemas\Notify\Node\EmailNotificationV1;
use Acme\Schemas\Notify\Node\IosNotificationV1;
use Gdbots\Ncr\Ncr;
use Gdbots\Ncr\Repository\InMemoryNcr;
use Gdbots\Schemas\Ncr\Enum\NodeStatus;
use Triniti\News\ArticleAggregate;
use Triniti\Notify\Exception\InvalidNotificationContent;
use Triniti\Notify\NotificationAggregate;
use Triniti\Notify\NotificationEnricher;
use Triniti\Schemas\Notify\Enum\NotificationSendStatus;
use Triniti\Tests\AbstractPbjxTest;

final class NotificationAggregateTest extends AbstractPbjxTest
{
    protected Ncr $ncr;

    protected function setup(): void
    {
        parent::setup();
        $this->ncr = new InMemoryNcr();
        $this->locator->getDispatcher()->addSubscriber(new NotificationEnricher($this->ncr));
    }

    public function testCreateNotification(): void
    {
        $article = ArticleV1::create();
        $articleRef = $article->generateNodeRef();
        $this->ncr->putNode($article);

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
        $this->ncr->putNode($page);

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
        $this->ncr->putNode($article);
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
        $this->ncr->putNode($article);
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

}
