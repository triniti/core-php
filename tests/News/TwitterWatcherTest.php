<?php
declare(strict_types=1);

namespace Triniti\Tests\News;

use Acme\Schemas\News\Node\ArticleV1;
use Acme\Schemas\Notify\Event\NotificationSentV1;
use Acme\Schemas\Notify\Node\TwitterNotificationV1;
use Gdbots\Ncr\AggregateResolver;
use Gdbots\Ncr\Event\NodeProjectedEvent;
use Gdbots\Ncr\Repository\InMemoryNcr;
use Gdbots\Pbj\WellKnown\NodeRef;
use Gdbots\Schemas\Pbjx\StreamId;
use Triniti\News\ArticleAggregate;
use Triniti\News\TwitterWatcher;
use Triniti\Schemas\Notify\NotifierResultV1;
use Triniti\Tests\AbstractPbjxTest;

final class TwitterWatcherTest extends AbstractPbjxTest
{
    protected InMemoryNcr $ncr;

    public function setup(): void
    {
        parent::setup();
        AggregateResolver::register(['acme:article' => ArticleAggregate::class]);
    }

    public function testNotifyTwitter(): void
    {
        $article = ArticleV1::create();
        $articleRef = NodeRef::fromNode($article);
        $notification = TwitterNotificationV1::create()->set('content_ref', $articleRef);
        $result = NotifierResultV1::create();

        $notificationSent = NotificationSentV1::create()
            ->set('node_ref', NodeRef::fromNode($notification))
            ->set('notifier_result', $result);

        $this->ncr->putNode($article);
        $this->ncr->putNode($notification);
        $watcher = new TwitterWatcher();
        $watcher->onArticlePublished(new NodeProjectedEvent($notification, $notificationSent));
        $streamId = StreamId::fromString(sprintf('acme:%s:%s', $articleRef->getLabel(), $articleRef->getId()));
        $slice = $this->pbjx->getEventStore()->getStreamSlice($streamId);

        $actualEvent = $slice->toArray()['events'][0];
    }
}
