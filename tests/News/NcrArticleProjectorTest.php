<?php
declare(strict_types=1);

namespace Triniti\Tests\News;

use Acme\Schemas\News\Event\AppleNewsArticleSyncedV1;
use Acme\Schemas\News\Event\ArticlePublishedV1;
use Acme\Schemas\News\Event\ArticleSlottingRemovedV1;
use Acme\Schemas\News\Event\ArticleUpdatedV1;
use Acme\Schemas\News\Node\ArticleV1;
use Gdbots\Ncr\Repository\InMemoryNcr;
use Gdbots\Pbj\Message;
use Gdbots\Pbj\WellKnown\UuidIdentifier;
use Gdbots\Pbjx\EventStore\InMemoryEventStore;
use Gdbots\Pbjx\RegisteringServiceLocator;
use Gdbots\Pbjx\Scheduler\Scheduler;
use Gdbots\Schemas\Ncr\Enum\NodeStatus;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Triniti\News\NcrArticleProjector;
use Triniti\Schemas\News\Command\RemoveArticleSlottingV1;
use Triniti\Tests\AbstractPbjxTest;
use Triniti\Tests\MockNcrSearch;

final class NcrArticleProjectorTest extends AbstractPbjxTest
{
    protected NcrArticleProjector $projector;
    protected InMemoryNcr $ncr;
    protected MockNcrSearch $ncrSearch;
    protected Scheduler $scheduler;
    protected RegisteringServiceLocator $locator;
    protected InMemoryEventStore $eventStore;

    public function setup(): void
    {
        parent::setup();
        $this->locator = new RegisteringServiceLocator();
        $this->pbjx = $this->locator->getPbjx();
        $this->eventStore = new InMemoryEventStore($this->pbjx);
        $this->locator->setEventStore($this->eventStore);
        $this->ncr = new InMemoryNcr();
        $this->ncrSearch = new MockNcrSearch();
        $this->projector = new NcrArticleProjector($this->ncr, $this->ncrSearch, new ArrayAdapter());

        $this->scheduler = new class implements Scheduler
        {
            public array $lastSendAt = [];
            public array $lastCancelJobs = [];

            public function createStorage(array $context = []): void
            {
            }

            public function describeStorage(array $context = []): string
            {
                return '';
            }

            public function sendAt(Message $command, int $timestamp, ?string $jobId = null, array $context = []): string
            {
                $this->lastSendAt = [
                    'command'   => $command,
                    'timestamp' => $timestamp,
                    'job_id'    => $jobId,
                ];

                return $jobId ?: 'jobid';
            }

            public function cancelJobs(array $jobIds, array $context = []): void
            {
                $this->lastCancelJobs = $jobIds;
            }

            public function getLastSendAt(): array
            {
                return $this->lastSendAt;
            }
        };

        $this->locator->setScheduler($this->scheduler);
    }

    public function testOnAppleNewsArticleSyncedDelete(): void
    {
        $article = ArticleV1::create()
            ->set('apple_news_id', UuidIdentifier::generate())
            ->set('apple_news_revision', 'AAAAAAAAAAAAAAAAAAAAAA==')
            ->set('apple_news_share_url', 'https://apple.news/foo')
            ->set('apple_news_updated_at', (new \DateTime())->getTimestamp());
        $this->ncr->putNode($article);
        $nodeRef = $article->generateNodeRef();
        $event = AppleNewsArticleSyncedV1::create()
            ->set('node_ref', $nodeRef)
            ->set('apple_news_operation', 'delete');
        $this->projector->onAppleNewsArticleSynced($event, $this->pbjx);

        $actualArticle = $this->ncr->getNode($nodeRef);
        $this->assertFalse($actualArticle->has('apple_news_id'));
        $this->assertFalse($actualArticle->has('apple_news_revision'));
        $this->assertFalse($actualArticle->has('apple_news_share_url'));
        $this->assertFalse($actualArticle->has('apple_news_updated_at'));
        $this->assertFalse($actualArticle->get('apple_news_enabled'));
    }

    public function testOnAppleNewsArticleSyncedNotDelete(): void
    {
        $article = ArticleV1::create();
        $this->ncr->putNode($article);
        $nodeRef = $article->generateNodeRef();
        $appleNewsId = UuidIdentifier::generate();
        $appleNewsRevision = 'AAAAAAAAAAAAAAAAAAAAAA==';
        $appleNewsShareUrl = 'https://apple.news/foo';
        $event = AppleNewsArticleSyncedV1::create()
            ->set('node_ref', $nodeRef)
            ->set('apple_news_id', $appleNewsId)
            ->set('apple_news_revision', $appleNewsRevision)
            ->set('apple_news_share_url', $appleNewsShareUrl);
        $this->projector->onAppleNewsArticleSynced($event, $this->pbjx);

        $actualArticle = $this->ncr->getNode($nodeRef);
        $this->assertTrue($appleNewsId->equals($actualArticle->get('apple_news_id')));
        $this->assertSame($appleNewsRevision, $actualArticle->get('apple_news_revision'));
        $this->assertSame($appleNewsShareUrl, $actualArticle->get('apple_news_share_url'));
        $this->assertSame($event->get('occurred_at')->toDateTime()->getTimestamp(), $actualArticle->get('apple_news_updated_at'));
    }

    public function testOnArticlePublished(): void
    {
        $article = ArticleV1::create()->addToMap('slotting', 'home', 1);
        $this->ncr->putNode($article);
        $nodeRef = $article->generateNodeRef();
        $event = ArticlePublishedV1::create()->set('node_ref', $nodeRef);
        $this->projector->onArticlePublished($event, $this->pbjx);

        $sentCommand = $this->scheduler->getLastSendAt()['command'];
        $this->assertInstanceOf(RemoveArticleSlottingV1::class, $sentCommand);
        $this->assertSame(1, $sentCommand->get('slotting')['home']);
    }

    public function testOnArticleSlottingRemoved(): void
    {
        $article = ArticleV1::create()->addToMap('slotting', 'home', 1);
        $this->ncr->putNode($article);
        $nodeRef = $article->generateNodeRef();
        $event = ArticleSlottingRemovedV1::create()
            ->set('node_ref', $nodeRef)
            ->addToSet('slotting_keys', ['home']);
        $this->projector->onArticleSlottingRemoved($event, $this->pbjx);

        $actualArticle = $this->ncr->getNode($nodeRef);
        $this->assertEmpty($actualArticle->get('slotting'));
    }

    public function testOnArticleUpdated(): void
    {
        $oldArticle = ArticleV1::create();
        $nodeRef = $oldArticle->generateNodeRef();
        $this->ncr->putNode($oldArticle);

        $newArticle = (clone $oldArticle);
        $newArticle
            ->set('title', 'New article')
            ->set('etag', $newArticle->generateEtag(['etag', 'updated_at']))
            ->set('status', NodeStatus::PUBLISHED())
            ->addToMap('slotting', 'home', 1);

        $event = ArticleUpdatedV1::create()
            ->set('old_node', $oldArticle)
            ->set('new_node', $newArticle)
            ->set('old_etag', $oldArticle->get('etag'))
            ->set('new_etag', $newArticle->get('etag'))
            ->set('node_ref', $nodeRef);

        $this->projector->onArticleUpdated($event, $this->pbjx);
        $sentCommand = $this->scheduler->getLastSendAt()['command'];
        $this->assertTrue($nodeRef->equals($sentCommand->get('except_ref')));
        $this->assertSame(1, $sentCommand->get('slotting')['home']);
    }

    public function testOnArticleUpdatedNoOldNode(): void
    {
        $article = ArticleV1::create();
        $nodeRef = $article->generateNodeRef();

        $event = ArticleUpdatedV1::create()
            ->set('new_node', $article)
            ->set('node_ref', $nodeRef);

        $this->projector->onArticleUpdated($event, $this->pbjx);
        $this->assertEmpty($this->scheduler->getLastSendAt());
    }
}
