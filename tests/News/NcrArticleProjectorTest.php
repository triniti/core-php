<?php
declare(strict_types=1);

namespace Triniti\Tests\News;

use Acme\Schemas\News\Event\AppleNewsArticleSyncedV1;
use Acme\Schemas\News\Event\ArticleUpdatedV1;
use Acme\Schemas\News\Node\ArticleV1;
use Gdbots\Ncr\AggregateResolver;
use Gdbots\Ncr\Repository\InMemoryNcr;
use Gdbots\Pbj\Message;
use Gdbots\Pbj\WellKnown\UuidIdentifier;
use Gdbots\Pbjx\EventStore\InMemoryEventStore;
use Gdbots\Pbjx\RegisteringServiceLocator;
use Gdbots\Pbjx\Scheduler\Scheduler;
use Triniti\News\ArticleAggregate;
use Triniti\News\NcrArticleProjector;
use Triniti\Schemas\Notify\Event\NotificationSentV1;
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
        $this->projector = new NcrArticleProjector($this->ncr, $this->ncrSearch);

        $this->scheduler = new class implements Scheduler {
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
        AggregateResolver::register(['acme:article' => ArticleAggregate::class]);
    }

    public function testOnAppleNewsArticleSyncedDelete(): void
    {
        $this->markTestIncomplete('need to use aggregates instead of ncr');

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
        $this->projector->onNodeEvent($event, $this->pbjx);

        $actualArticle = $this->ncr->getNode($nodeRef);
        $this->assertFalse($actualArticle->has('apple_news_id'));
        $this->assertFalse($actualArticle->has('apple_news_revision'));
        $this->assertFalse($actualArticle->has('apple_news_share_url'));
        $this->assertFalse($actualArticle->has('apple_news_updated_at'));
        $this->assertFalse($actualArticle->get('apple_news_enabled'));
    }

    public function testOnAppleNewsArticleSyncedNotDelete(): void
    {
        $this->markTestIncomplete('need to use aggregates instead of ncr');

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
        $this->projector->onNodeEvent($event, $this->pbjx);

        $actualArticle = $this->ncr->getNode($nodeRef);
        $this->assertSame((string)$appleNewsId, (string)$actualArticle->get('apple_news_id'));
        $this->assertSame($appleNewsRevision, $actualArticle->get('apple_news_revision'));
        $this->assertSame($appleNewsShareUrl, $actualArticle->get('apple_news_share_url'));
        $this->assertSame($event->get('occurred_at')->toDateTime()->getTimestamp(), $actualArticle->get('apple_news_updated_at'));
    }
}
