<?php
declare(strict_types=1);

namespace Triniti\Notify;

use Gdbots\Ncr\Aggregate;
use Gdbots\Ncr\AggregateResolver;
use Gdbots\Pbj\Message;
use Gdbots\Pbj\MessageResolver;
use Gdbots\Pbj\WellKnown\NodeRef;
use Gdbots\Schemas\Ncr\Enum\NodeStatus;
use Triniti\Notify\Exception\InvalidNotificationContent;
use Triniti\Schemas\Notify\Enum\NotificationSendStatus;
use Triniti\Schemas\Notify\Event\NotificationFailedV1;
use Triniti\Schemas\Notify\Event\NotificationSentV1;

class NotificationAggregate extends Aggregate
{
    use NotificationPbjxHelperTrait;

    public function failNotification(Message $command, Message $result): void
    {
        $event = NotificationFailedV1::create();
        $this->pbjx->copyContext($command, $event);
        $event
            ->set('node_ref', $this->nodeRef)
            ->set('notifier_result', $result);
        $this->recordEvent($event);
    }

    public function sendNotification(Message $command, Message $result): void
    {
        $event = NotificationSentV1::create();
        $this->pbjx->copyContext($command, $event);
        $event
            ->set('node_ref', $this->nodeRef)
            ->set('notifier_result', $result);
        $this->recordEvent($event);
    }

    protected function applyNotificationSent(Message $event): void
    {
        $this->node
            ->set('send_status', NotificationSendStatus::SENT())
            ->set('sent_at', $event->get('occurred_at')->toDateTime())
            ->set('notifier_result', $event->get('notifier_result'));
    }

    protected function applyNotificationFailed(Message $event): void
    {
        $this->node
            ->set('send_status', NotificationSendStatus::FAILED())
            ->set('sent_at', $event->get('occurred_at')->toDateTime())
            ->set('notifier_result', $event->get('notifier_result'));
    }

    protected function enrichNodeCreated(Message $event): void
    {
        parent::enrichNodeCreated($event);

        /** @var Message $node */
        $node = $event->get('node');
        $node
            ->clear('sent_at')
            ->set('status', NodeStatus::PUBLISHED());

        $this->applySchedule($node);
    }

    protected function enrichNodeUpdated(Message $event): void
    {
        parent::enrichNodeUpdated($event);

        /** @var Message $oldNode */
        $oldNode = $event->get('old_node');

        /** @var Message $newNode */
        $newNode = $event->get('new_node');

        $newNode
            // app_ref SHOULD NOT change during an update
            ->set('app_ref', $oldNode->get('app_ref'))
            // content_ref SHOULD NOT change during an update
            ->set('content_ref', $oldNode->get('content_ref'))
            // send_status SHOULD NOT change during an update by
            // a user action, it MAY change when scheduling is applied
            ->set('send_status', $oldNode->get('send_status'))
            // sent_at SHOULD NOT change during an update,
            // it is updated when notifications are sent
            ->set('sent_at', $oldNode->get('sent_at'));

        // notifications are only published or deleted, enforce it.
        if (!NodeStatus::DELETED()->equals($newNode->get('status'))) {
            $newNode->set('status', NodeStatus::PUBLISHED());
        }

        $this->applySchedule($newNode, false);
    }

    /**
     * @param Message $notification
     * @param bool    $updateStatus
     *
     * @throws InvalidNotificationContent
     */
    protected function applySchedule(Message $notification, bool $updateStatus = true): void
    {
        if ($this->alreadySent($notification)) {
            // schedule cannot change at this point.
            return;
        }

        if (!$notification->has('send_at')
            && $notification->has('content_ref')
            && $notification->get('send_on_publish')
        ) {
            /** @var NodeRef $contentRef */
            $contentRef = $notification->get('content_ref');
            $aggregate = AggregateResolver::resolve($contentRef->getQName())::fromNodeRef($contentRef, $this->pbjx);
            $aggregate->sync();
            $content = $aggregate->getNode();

            if (
                !$content::schema()->hasMixin('triniti:notify:mixin:has-notifications')
                || !$content::schema()->hasMixin('gdbots:ncr:mixin:publishable')
            ) {
                throw new InvalidNotificationContent();
            }

            $notification->set('title', $content->get('title'));
            if ($content->has('published_at')) {
                $sendAt = clone $content->get('published_at');
                $notification->set('send_at', $sendAt->modify('+10 seconds'));
            }
        }

        if ($updateStatus) {
            if ($notification->has('send_at')) {
                $notification->set('send_status', NotificationSendStatus::SCHEDULED());
            } else {
                $notification->set('send_status', NotificationSendStatus::DRAFT());
            }
        }
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
        $newName = str_replace('Notification', 'Node', $name);
        if ($newName !== $name && is_callable([$this, $newName])) {
            return $this->$newName(...$arguments);
        }
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
        return MessageResolver::resolveCurie('*:notify:event:notification-expired:v1')::create();
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
        return MessageResolver::resolveCurie('*:notify:event:notification-locked:v1')::create();
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
        return MessageResolver::resolveCurie('*:notify:event:notification-marked-as-draft:v1')::create();
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
        return MessageResolver::resolveCurie('*:notify:event:notification-marked-as-pending:v1')::create();
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
        return MessageResolver::resolveCurie('*:notify:event:notification-published:v1')::create();
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
        return MessageResolver::resolveCurie('*:notify:event:notification-renamed:v1')::create();
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
        return MessageResolver::resolveCurie('*:notify:event:notification-scheduled:v1')::create();
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
        return MessageResolver::resolveCurie('*:notify:event:notification-unlocked:v1')::create();
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
        return MessageResolver::resolveCurie('*:notify:event:notification-unpublished:v1')::create();
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
        return MessageResolver::resolveCurie('*:notify:event:notification-updated:v1')::create();
    }
}
