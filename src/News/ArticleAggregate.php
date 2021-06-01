<?php
declare(strict_types=1);

namespace Triniti\News;

use Gdbots\Ncr\Aggregate;
use Gdbots\Pbj\Message;
use Gdbots\Pbj\MessageResolver;
use Gdbots\Pbj\Util\StringUtil;
use Gdbots\Pbj\WellKnown\NodeRef;
use Gdbots\Pbj\WellKnown\UuidIdentifier;

class ArticleAggregate extends Aggregate
{
    public function onAppleNewsNotificationSent(Message $event, Message $notification): void
    {
        if (!$notification->has('content_ref') || !$event->has('notifier_result')) {
            return;
        }

        /** @var Message $result */
        $result = $event->get('notifier_result');
        $operation = $result->getFromMap('tags', 'apple_news_operation');
        if ('notification' === $operation) {
            return;
        }

        /** @var NodeRef $contentRef */
        $contentRef = $notification->get('content_ref');
        $this->assertNodeRefMatches($contentRef);

        $syncedEvent = $this->createAppleNewsArticleSynced($event)
            ->set('node_ref', $this->nodeRef)
            ->set('notification_ref', $notification->generateNodeRef())
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

        $this->copyContext($event, $syncedEvent);
        $this->recordEvent($syncedEvent);
    }

    public function removeArticleSlotting(Message $command): void
    {
        if (!$command->has('slotting')) {
            return;
        }

        $slottingKeys = [];
        foreach ($command->get('slotting') as $key => $value) {
            $currentSlot = $this->node->getFromMap('slotting', $key, 0);
            if ($currentSlot === $value) {
                $slottingKeys[] = $key;
            }
        }

        if (empty($slottingKeys)) {
            return;
        }

        $event = $this->createArticleSlottingRemoved($command);
        $this->copyContext($command, $event);
        $event->set('node_ref', $this->nodeRef);
        $event->addToSet('slotting_keys', $slottingKeys);
        $this->recordEvent($event);
    }

    protected function applyAppleNewsArticleSynced(Message $event): void
    {
        if ('delete' === $event->get('apple_news_operation')) {
            $this->node
                ->clear('apple_news_id')
                ->clear('apple_news_revision')
                ->clear('apple_news_share_url')
                ->clear('apple_news_updated_at')
                ->set('apple_news_enabled', false);
            return;
        }

        /** @var \DateTime $occurredAt */
        $occurredAt = $event->get('occurred_at')->toDateTime();
        $this->node
            ->set('apple_news_id', $event->get('apple_news_id'))
            ->set('apple_news_revision', $event->get('apple_news_revision'))
            ->set('apple_news_share_url', $event->get('apple_news_share_url'))
            ->set('apple_news_updated_at', $occurredAt->getTimestamp());
    }

    protected function applyArticleSlottingRemoved(Message $event): void
    {
        foreach ($event->get('slotting_keys', []) as $key) {
            $this->node->removeFromMap('slotting', $key);
        }
    }

    protected function applyNodePublished(Message $event): void
    {
        parent::applyNodePublished($event);

        if ($this->node::schema()->hasMixin('triniti:curator:mixin:teaserable')) {
            $this->node->set('order_date', $event->get('published_at'));
        }
    }

    protected function enrichNodeUpdated(Message $event): void
    {
        /** @var Message $oldNode */
        $oldNode = $event->get('old_node');

        /** @var Message $newNode */
        $newNode = $event->get('new_node');

        // some apple news fields should NOT change during an update
        $newNode->set('apple_news_updated_at', $oldNode->get('apple_news_updated_at'));

        if ($event->has('paths')) {
            foreach (['apple_news_id', 'apple_news_revision', 'apple_news_share_url'] as $path) {
                if (!$event->isInSet('paths', $path)) {
                    $newNode->set($path, $oldNode->get($path));
                }
            }
        }

        parent::enrichNodeUpdated($event);
    }

    /**
     * This is for legacy uses of command/event mixins for common
     * ncr operations. It will be removed in 3.x.
     *
     * @param string $name
     * @param array  $arguments
     *
     * @return mixed
     */
    public function __call(string $name, array $arguments)
    {
        $newName = str_replace('Article', 'Node', $name);
        if ($newName !== $name && is_callable([$this, $newName])) {
            return $this->$newName(...$arguments);
        }
    }

    /**
     * This is for legacy uses of command/event mixins for common
     * ncr operations. It will be removed in 3.x.
     *
     * @param Message $event
     *
     * @return Message
     *
     * @deprecated Will be removed in 3.x.
     */
    protected function createAppleNewsArticleSynced(Message $event): Message
    {
        return MessageResolver::resolveCurie('*:news:event:apple-news-article-synced:v1')::create();
    }

    /**
     * This is for legacy uses of command/event mixins for common
     * ncr operations. It will be removed in 3.x.
     *
     * @param Message $command
     *
     * @return Message
     *
     * @deprecated Will be removed in 3.x.
     */
    protected function createArticleSlottingRemoved(Message $command): Message
    {
        return MessageResolver::resolveCurie('*:news:event:article-slotting-removed:v1')::create();
    }

    /**
     * This is for legacy uses of command/event mixins for common
     * ncr operations. It will be removed in 3.x.
     *
     * @param Message $command
     *
     * @return Message
     *
     * @deprecated Will be removed in 3.x.
     */
    protected function createNodeCreatedEvent(Message $command): Message
    {
        return MessageResolver::resolveCurie('*:news:event:article-created:v1')::create();
    }

    /**
     * This is for legacy uses of command/event mixins for common
     * ncr operations. It will be removed in 3.x.
     *
     * @param Message $command
     *
     * @return Message
     *
     * @deprecated Will be removed in 3.x.
     */
    protected function createNodeDeletedEvent(Message $command): Message
    {
        return MessageResolver::resolveCurie('*:news:event:article-deleted:v1')::create();
    }

    /**
     * This is for legacy uses of command/event mixins for common
     * ncr operations. It will be removed in 3.x.
     *
     * @param Message $command
     *
     * @return Message
     *
     * @deprecated Will be removed in 3.x.
     */
    protected function createNodeExpiredEvent(Message $command): Message
    {
        return MessageResolver::resolveCurie('*:news:event:article-expired:v1')::create();
    }

    /**
     * This is for legacy uses of command/event mixins for common
     * ncr operations. It will be removed in 3.x.
     *
     * @param Message $command
     *
     * @return Message
     *
     * @deprecated Will be removed in 3.x.
     */
    protected function createNodeLockedEvent(Message $command): Message
    {
        return MessageResolver::resolveCurie('*:news:event:article-locked:v1')::create();
    }

    /**
     * This is for legacy uses of command/event mixins for common
     * ncr operations. It will be removed in 3.x.
     *
     * @param Message $command
     *
     * @return Message
     *
     * @deprecated Will be removed in 3.x.
     */
    protected function createNodeMarkedAsDraftEvent(Message $command): Message
    {
        return MessageResolver::resolveCurie('*:news:event:article-marked-as-draft:v1')::create();
    }

    /**
     * This is for legacy uses of command/event mixins for common
     * ncr operations. It will be removed in 3.x.
     *
     * @param Message $command
     *
     * @return Message
     *
     * @deprecated Will be removed in 3.x.
     */
    protected function createNodeMarkedAsPendingEvent(Message $command): Message
    {
        return MessageResolver::resolveCurie('*:news:event:article-marked-as-pending:v1')::create();
    }

    /**
     * This is for legacy uses of command/event mixins for common
     * ncr operations. It will be removed in 3.x.
     *
     * @param Message $command
     *
     * @return Message
     *
     * @deprecated Will be removed in 3.x.
     */
    protected function createNodePublishedEvent(Message $command): Message
    {
        return MessageResolver::resolveCurie('*:news:event:article-published:v1')::create();
    }

    /**
     * This is for legacy uses of command/event mixins for common
     * ncr operations. It will be removed in 3.x.
     *
     * @param Message $command
     *
     * @return Message
     *
     * @deprecated Will be removed in 3.x.
     */
    protected function createNodeRenamedEvent(Message $command): Message
    {
        return MessageResolver::resolveCurie('*:news:event:article-renamed:v1')::create();
    }

    /**
     * This is for legacy uses of command/event mixins for common
     * ncr operations. It will be removed in 3.x.
     *
     * @param Message $command
     *
     * @return Message
     *
     * @deprecated Will be removed in 3.x.
     */
    protected function createNodeScheduledEvent(Message $command): Message
    {
        return MessageResolver::resolveCurie('*:news:event:article-scheduled:v1')::create();
    }

    /**
     * This is for legacy uses of command/event mixins for common
     * ncr operations. It will be removed in 3.x.
     *
     * @param Message $command
     *
     * @return Message
     *
     * @deprecated Will be removed in 3.x.
     */
    protected function createNodeUnlockedEvent(Message $command): Message
    {
        return MessageResolver::resolveCurie('*:news:event:article-unlocked:v1')::create();
    }

    /**
     * This is for legacy uses of command/event mixins for common
     * ncr operations. It will be removed in 3.x.
     *
     * @param Message $command
     *
     * @return Message
     *
     * @deprecated Will be removed in 3.x.
     */
    protected function createNodeUnpublishedEvent(Message $command): Message
    {
        return MessageResolver::resolveCurie('*:news:event:article-unpublished:v1')::create();
    }

    /**
     * This is for legacy uses of command/event mixins for common
     * ncr operations. It will be removed in 3.x.
     *
     * @param Message $command
     *
     * @return Message
     *
     * @deprecated Will be removed in 3.x.
     */
    protected function createNodeUpdatedEvent(Message $command): Message
    {
        return MessageResolver::resolveCurie('*:news:event:article-updated:v1')::create();
    }
}
