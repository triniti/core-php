<?php
declare(strict_types=1);

namespace Triniti\Tests\News;

use Acme\Schemas\Iam\Node\TwitterAppV1;
use Acme\Schemas\News\Event\ArticlePublishedV1;
use Acme\Schemas\News\Node\ArticleV1;
use Acme\Schemas\Notify\Event\NotificationSentV1;
use Acme\Schemas\Notify\Node\TwitterNotificationV1;
use Gdbots\Ncr\AggregateResolver;
use Gdbots\Ncr\Event\NodeProjectedEvent;
use Gdbots\Ncr\Repository\InMemoryNcr;
use Gdbots\Pbj\Message;
use Gdbots\Pbj\WellKnown\NodeRef;
use Gdbots\Pbjx\Pbjx;
use Gdbots\Schemas\Iam\Request\SearchAppsRequestV1;
use Gdbots\Schemas\Ncr\Enum\NodeStatus;
use Gdbots\Schemas\Pbjx\StreamId;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Triniti\News\ArticleAggregate;
use Triniti\News\TwitterWatcher;
use Triniti\Notify\Notifier\TwitterNotifier;
use Triniti\Schemas\Notify\NotifierResultV1;
use Triniti\Sys\Flags;
use Triniti\Tests\AbstractPbjxTest;
use Triniti\Tests\MockPbjx;

final class TwitterWatcherTest extends AbstractPbjxTest
{
    protected InMemoryNcr $ncr;
    protected TwitterWatcher $watcher;

    public function setup(): void
    {
        parent::setup();
        $this->ncr = new InMemoryNcr();
        $this->watcher = new class() extends TwitterWatcher
        {
            protected function getApp(Message $article, Message $event, Pbjx $pbjx): ?Message
            {
                 return TwitterAppV1::create();
            }
        };
    }

    public function testNotifyTwitter(): void
    {
        $article = ArticleV1::create();
        $articleRef = NodeRef::fromNode($article);
        $this->pbjx = new MockPbjx($this->locator);

        $articlePublished = ArticlePublishedV1::create()
            ->set('node_ref', $articleRef);
        $nodeProjectedEvent = new NodeProjectedEvent($article, $articlePublished);
        $nodeProjectedEvent::setPbjx($this->pbjx);

        $this->ncr->putNode($article);
        $this->ncr->putNode($articlePublished);
        $this->watcher->onArticlePublished($nodeProjectedEvent);
//        $watcher = new TwitterWatcher();
//        $watcher->onArticlePublished(new NodeProjectedEvent($notification, $notificationSent));
//        $streamId = StreamId::fromString(sprintf('acme:%s:%s', $articleRef->getLabel(), $articleRef->getId()));
//        $slice = $this->pbjx->getEventStore()->getStreamSlice($streamId);
//
//        $actualEvent = $slice->toArray()['events'][0];
    }
}
