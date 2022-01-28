<?php
declare(strict_types=1);

namespace Triniti\Tests\News;

use Acme\Schemas\Iam\Node\TwitterAppV1;
use Acme\Schemas\News\Event\ArticlePublishedV1;
use Acme\Schemas\News\Node\ArticleV1;
use Gdbots\Ncr\Event\NodeProjectedEvent;
use Gdbots\Pbj\Message;
use Gdbots\Pbj\WellKnown\NodeRef;
use Gdbots\Pbj\WellKnown\UuidIdentifier;
use Gdbots\Pbjx\Pbjx;
use Ramsey\Uuid\Uuid;
use Triniti\News\TwitterWatcher;
use Triniti\Tests\AbstractPbjxTest;
use Triniti\Tests\MockPbjx;

final class TwitterWatcherTest extends AbstractPbjxTest
{
    protected TwitterWatcher $watcher;

    public function setup(): void
    {
        parent::setup();
        $this->pbjx = new MockPbjx($this->locator);
        $this->watcher = new class() extends TwitterWatcher {
            private ?Message $app = null;

            protected function getApps(Message $article, Message $event, Pbjx $pbjx): array
            {
                return [$this->getApp()];
            }

            public function getApp(): Message
            {
                if (null === $this->app) {
                    $this->app = TwitterAppV1::create();
                }
                return $this->app;
            }
        };
    }

    public function testOnArticlePublished(): void
    {
        $article = ArticleV1::create();
        $articleRef = NodeRef::fromNode($article);

        $articlePublished = ArticlePublishedV1::create()
            ->set('node_ref', $articleRef);
        $nodeProjectedEvent = new NodeProjectedEvent($article, $articlePublished);
        $nodeProjectedEvent::setPbjx($this->pbjx);

        $this->watcher->onArticlePublished($nodeProjectedEvent);
        $this->assertSame($articleRef->toString(), $this->pbjx->getSent()[0]['command']->get('node')->get('content_ref')->toString());
    }

    public function testOnArticlePublishedTwitterPublishDisabled(): void
    {
        $article = ArticleV1::create()->set('twitter_publish_enabled', false);
        $articleRef = NodeRef::fromNode($article);

        $articlePublished = ArticlePublishedV1::create()
            ->set('node_ref', $articleRef);
        $nodeProjectedEvent = new NodeProjectedEvent($article, $articlePublished);
        $nodeProjectedEvent::setPbjx($this->pbjx);

        $this->watcher->onArticlePublished($nodeProjectedEvent);
        $this->assertEmpty($this->pbjx->getSent());
    }

    public function testTwitterNotificationIdGeneration(): void
    {
        $article = ArticleV1::create();
        $articleRef = NodeRef::fromNode($article);

        $articlePublished = ArticlePublishedV1::create()
            ->set('node_ref', $articleRef);
        $nodeProjectedEvent = new NodeProjectedEvent($article, $articlePublished);
        $nodeProjectedEvent::setPbjx($this->pbjx);
        $expectedId = UuidIdentifier::fromString(
            Uuid::uuid5(
                Uuid::uuid5(Uuid::NIL, 'twitter-auto-post'),
                $article->generateNodeRef()->toString() . $this->watcher->getApp()->fget('_id')
            )->toString()
        );

        $this->watcher->onArticlePublished($nodeProjectedEvent);
        $this->assertSame($expectedId->toString(), $this->pbjx->getSent()[0]['command']->get('node')->get('_id')->toString());
    }
}
