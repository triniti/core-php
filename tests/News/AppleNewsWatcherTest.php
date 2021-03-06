<?php
declare(strict_types=1);

namespace Triniti\Tests\News;

use Acme\Schemas\News\Node\ArticleV1;
use Acme\Schemas\Notify\Event\NotificationSentV1;
use Acme\Schemas\Notify\Node\AppleNewsNotificationV1;
use Gdbots\Ncr\AggregateResolver;
use Gdbots\Ncr\Event\NodeProjectedEvent;
use Gdbots\Ncr\Repository\InMemoryNcr;
use Gdbots\Pbj\Util\StringUtil;
use Gdbots\Pbj\WellKnown\NodeRef;
use Gdbots\Schemas\Pbjx\StreamId;
use Triniti\News\AppleNewsWatcher;
use Triniti\News\ArticleAggregate;
use Triniti\Schemas\Notify\NotifierResultV1;
use Triniti\Tests\AbstractPbjxTest;

final class AppleNewsWatcherTest extends AbstractPbjxTest
{
    protected InMemoryNcr $ncr;

    public function setup(): void
    {
        parent::setup();
        $this->ncr = new InMemoryNcr();
        AggregateResolver::register(['acme:article' => ArticleAggregate::class]);
    }

    public function testOnNotificationSent(): void
    {
        $article = ArticleV1::create();
        $articleRef = NodeRef::fromNode($article);

        $expectedRevision = 'AAAAAAAAAAAAAAAAAAAAAQ==';

        $notification = AppleNewsNotificationV1::create()->set('content_ref', $articleRef);
        $result = NotifierResultV1::create()
            ->addToMap('tags', 'apple_news_operation', 'create')
            ->addToMap('tags', 'apple_news_id', '1fd3f344-28c1-4ad3-acb3-f32eac206401')
            ->addToMap('tags', 'apple_news_share_url', 'https://share.com')
            ->addToMap(
                'tags',
                'apple_news_revision',
                StringUtil::urlsafeB64Encode($expectedRevision)
            );

        $notificationSent = NotificationSentV1::create()
            ->set('node_ref', NodeRef::fromNode($notification))
            ->set('notifier_result', $result);

        $this->ncr->putNode($article);
        $this->ncr->putNode($notification);
        $watcher = new AppleNewsWatcher($this->ncr);
        $watcher->onNotificationSent(new NodeProjectedEvent($notification, $notificationSent));

        $streamId = StreamId::fromString(sprintf('acme:%s:%s', $articleRef->getLabel(), $articleRef->getId()));
        $slice = $this->pbjx->getEventStore()->getStreamSlice($streamId);

        $actualEvent = $slice->toArray()['events'][0];

        $this->assertEquals(
            $result->getFromMap('tags', 'apple_news_id'),
            $actualEvent->get('apple_news_id')
        );

        $this->assertEquals($expectedRevision, $actualEvent->get('apple_news_revision'));

        $this->assertEquals(
            $result->getFromMap('tags', 'apple_news_share_url'),
            $actualEvent->get('apple_news_share_url')
        );
    }
}
