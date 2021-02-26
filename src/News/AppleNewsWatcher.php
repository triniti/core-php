<?php
declare(strict_types=1);

namespace Triniti\News;

use Gdbots\Ncr\Exception\NodeNotFound;
use Gdbots\Ncr\Ncr;
use Gdbots\Pbj\Message;
use Gdbots\Pbj\MessageResolver;
use Gdbots\Pbj\SchemaCurie;
use Gdbots\Pbj\Util\StringUtil;
use Gdbots\Pbj\WellKnown\NodeRef;
use Gdbots\Pbj\WellKnown\UuidIdentifier;
use Gdbots\Pbjx\EventSubscriber;
use Gdbots\Pbjx\EventSubscriberTrait;
use Gdbots\Pbjx\Pbjx;
use Gdbots\Schemas\Iam\Request\SearchAppsRequestV1;
use Gdbots\Schemas\Ncr\Enum\NodeStatus;
use Gdbots\Schemas\Pbjx\Enum\Code;
use Gdbots\Schemas\Pbjx\StreamId;
use Triniti\Schemas\News\Event\AppleNewsArticleSyncedV1;
use Triniti\Schemas\Notify\Mixin\AppleNewsNotification\AppleNewsNotificationV1Mixin;

class AppleNewsWatcher implements EventSubscriber
{
    use EventSubscriberTrait;

    protected Ncr $ncr;

    public function __construct(Ncr $ncr)
    {
        $this->ncr = $ncr;
    }

    public static function getSubscribedEvents()
    {
        $vendor = MessageResolver::getDefaultVendor();
        return [
            // run later than NcrArticleProjector to ensure we get latest node
            "{$vendor}:news:event:*"                   => ['onEvent', -100],
            'triniti:notify:mixin:notification-failed' => 'onNotificationFailed',
            'triniti:notify:mixin:notification-sent'   => 'onNotificationSent',
        ];
    }

    public function onArticleDeleted(Message $event, Pbjx $pbjx): void
    {
        $this->notifyAppleNews($event, $event->get('node_ref'), $pbjx, 'delete');
    }

    public function onArticleExpired(Message $event, Pbjx $pbjx): void
    {
        $this->notifyAppleNews($event, $event->get('node_ref'), $pbjx, 'delete');
    }

    public function onArticlePublished(Message $event, Pbjx $pbjx): void
    {
        $this->notifyAppleNews($event, $event->get('node_ref'), $pbjx, 'create');
    }

    public function onArticleRenamed(Message $event, Pbjx $pbjx): void
    {
        if (!NodeStatus::PUBLISHED()->equals($event->get('node_status'))) {
            return;
        }

        $this->notifyAppleNews($event, $event->get('node_ref'), $pbjx, 'update');
    }

    public function onArticleUnpublished(Message $event, Pbjx $pbjx): void
    {
        $this->notifyAppleNews($event, $event->get('node_ref'), $pbjx, 'delete');
    }

    public function onArticleUpdated(Message $event, Pbjx $pbjx): void
    {
        if ($event->isReplay()) {
            return;
        }

        if ($event->get('old_etag') === $event->get('new_etag')) {
            return;
        }

        $oldNode = $event->get('old_node');
        $newNode = $event->get('new_node');

        if (!NodeStatus::PUBLISHED()->equals($newNode->get('status'))) {
            if ($newNode->has('apple_news_id')) {
                $operation = 'delete';
            } else {
                return;
            }
        } elseif (null === $oldNode) {
            if (!$newNode->get('apple_news_enabled') && $newNode->has('apple_news_id')) {
                $operation = 'delete';
            } elseif ($newNode->get('apple_news_enabled')) {
                $operation = $newNode->has('apple_news_id') ? 'update' : 'create';
            } else {
                return;
            }
        } elseif ($oldNode->get('apple_news_enabled') && !$newNode->get('apple_news_enabled')) {
            $operation = 'delete';
        } elseif ($newNode->get('apple_news_enabled')) {
            $operation = $newNode->has('apple_news_id') ? 'update' : 'create';
        } else {
            return;
        }

        $this->notifyAppleNews($event, $newNode, $pbjx, $operation);
    }

    public function onNotificationFailed(Message $event, Pbjx $pbjx): void
    {
        if ($event->isReplay()) {
            return;
        }

        $notificationRef = $event->get('node_ref');
        if ('apple-news-notification' !== $notificationRef->getLabel()) {
            return;
        }
        // todo: check why it failed and determine if a re-try is valid.
    }

    public function onNotificationSent(Message $event, Pbjx $pbjx): void
    {
        if ($event->isReplay()) {
            return;
        }

        $notificationRef = $event->get('node_ref');
        if ('apple-news-notification' !== $notificationRef->getLabel()) {
            return;
        }

        $result = $event->get('notifier_result');
        $operation = $result->getFromMap('tags', 'apple_news_operation');
        if ('notification' === $operation) {
            return;
        }

        try {
            $notification = $this->ncr->getNode($notificationRef, false);
            if (!$notification->has('content_ref')) {
                return;
            }
        } catch (NodeNotFound $nf) {
            return;
        } catch (\Throwable $e) {
            throw $e;
        }

        $contentRef = $notification->get('content_ref');

        $syncedEvent = $this->createAppleNewsArticleSynced($event, $pbjx)
            ->set('node_ref', $contentRef)
            ->set('notification_ref', $notificationRef)
            ->set('apple_news_operation', $operation);

        if ('delete' !== $operation) {
            $id = UuidIdentifier::fromString($result->getFromMap('tags', 'apple_news_id'));
            $revision = $result->getFromMap('tags', 'apple_news_revision', '');
            $shareUrl = $result->getFromMap('tags', 'apple_news_share_url');
            $syncedEvent
                ->set('apple_news_id', $id)
                ->set('apple_news_revision', StringUtil::urlsafeB64Decode($revision))
                ->set('apple_news_share_url', $shareUrl);
        }

        static $vendor = null;
        if ($vendor === null) {
            $vendor = MessageResolver::getDefaultVendor();
        }

        $streamId = StreamId::fromString(sprintf('%s:%s:%s', $vendor, $contentRef->getLabel(), $contentRef->getId()));
        $pbjx->getEventStore()->putEvents(
            $streamId, [$syncedEvent], null
        );
    }

    protected function createAppleNewsArticleSynced(Message $event, Pbjx $pbjx): Message
    {
        $syncedEvent = AppleNewsArticleSyncedV1::create();
        $pbjx->copyContext($event, $syncedEvent);
        return $syncedEvent;
    }

    protected function createAppleNewsNotification(Message $event, Message $article, Pbjx $pbjx): Message
    {
        $date = $event->get('occurred_at')->toDateTime();

        return AppleNewsNotificationV1Mixin::findOne()->createMessage()
            ->set('title', $article->get('title') . ' ' . $date->format('Y-m-d\TH:i:s\Z'))
            ->set('send_at', $date)
            ->set('content_ref', NodeRef::fromNode($article))
            ->set('apple_news_id', $article->get('apple_news_id'))
            ->set('apple_news_revision', $article->get('apple_news_revision'));
    }

    protected function getApp(Message $article, Message $event, Pbjx $pbjx): ?Message
    {
        $request = SearchAppsRequestV1::create();
        $response = $pbjx->copyContext($event, $request)->request($request);
        $published = NodeStatus::PUBLISHED();

        /** @var Message $node */
        foreach ($response->get('nodes', []) as $node) {
            if ($node instanceof AppleNewsApp && $published->equals($node->get('status'))) {
                return $node;
            }
        }

        return null;
    }

    protected function notifyAppleNews(Message $event, $article, Pbjx $pbjx, string $operation): void
    {
        if ($event->isReplay()) {
            return;
        }

        if ($article instanceof NodeRef) {
            try {
                $article = $this->ncr->getNode($article, false);
            } catch (NodeNotFound $nf) {
                return;
            } catch (\Throwable $e) {
                throw $e;
            }
        }

        if (!$article instanceof Message) {
            return;
        }

        if ('delete' === $operation && !$article->has('apple_news_id')) {
            return;
        }

        if ('create' === $operation && !$article->get('apple_news_enabled')) {
            return;
        }

        if (!$this->shouldNotifyAppleNews($event, $article)) {
            return;
        }

        $app = $this->getApp($article, $event, $pbjx);
        if (null === $app) {
            return;
        }

        $notification = $this->createAppleNewsNotification($event, $article, $pbjx)
            ->set('app_ref', NodeRef::fromNode($app))
            ->set('apple_news_operation', $operation);

        if ('update' === $operation && !$article->has('apple_news_id')) {
            $notification
                ->set('apple_news_operation', 'create')
                ->clear('apple_news_id')
                ->clear('apple_news_revision');
        }

        $curie = $notification::schema()->getCurie();
        $curie = "{$curie->getVendor()}:{$curie->getPackage()}:command:create-notification";

        $class = MessageResolver::resolveCurie(SchemaCurie::fromString($curie));
        $command = $class::create()->set('node', $notification);

        $pbjx->copyContext($event, $command);

        $operation = $notification->get('apple_news_operation');
        if ('create' === $operation) {
            try {
                $pbjx->send($command);
            } catch (\Throwable $e) {
                if ($e->getCode() !== Code::ALREADY_EXISTS) {
                    throw $e;
                }
            }

            return;
        }

        $nodeRef = NodeRef::fromNode($article);
        $pbjx->sendAt($command, strtotime('+180 seconds'), "{$nodeRef}.sync-apple-news");
    }

    /**
     * @param Message $event
     * @param Message $article
     *
     * @return bool
     */
    protected function shouldNotifyAppleNews(Message $event, Message $article): bool
    {
        // override to implement your own check to block apple news updates
        // based on the event or article
        return true;
    }
}
