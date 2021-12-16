<?php
declare(strict_types=1);

namespace Triniti\Notify;

use Gdbots\Ncr\Aggregate;
use Gdbots\Pbj\Message;
use Gdbots\Pbj\MessageResolver;
use Gdbots\Pbj\WellKnown\NodeRef;
use Gdbots\Schemas\Ncr\Enum\NodeStatus;
use Triniti\Notify\Exception\NotificationAlreadySent;
use Triniti\Schemas\Notify\Enum\NotificationSendStatus;

class NotificationAggregate extends Aggregate
{
    public function alreadySent(): bool
    {
        return NotificationValidator::alreadySent($this->node);
    }

    public function onNotificationFailed(Message $command, Message $result): void
    {
        /** @var NodeRef $nodeRef */
        $nodeRef = $command->get('node_ref');
        $this->assertNodeRefMatches($nodeRef);

        if ($this->alreadySent()) {
            throw new NotificationAlreadySent();
        }

        $event = $this->createNotificationFailed($command)
            ->set('node_ref', $this->nodeRef)
            ->set('notifier_result', $result);

        $this->copyContext($command, $event);
        $this->recordEvent($event);
    }

    public function onNotificationSent(Message $command, Message $result): void
    {
        /** @var NodeRef $nodeRef */
        $nodeRef = $command->get('node_ref');
        $this->assertNodeRefMatches($nodeRef);

        if ($this->alreadySent()) {
            throw new NotificationAlreadySent();
        }

        $event = $this->createNotificationSent($command)
            ->set('node_ref', $this->nodeRef)
            ->set('notifier_result', $result);

        $this->copyContext($command, $event);
        $this->recordEvent($event);
    }

    protected function applyNodeDeleted(Message $event): void
    {
        parent::applyNodeDeleted($event);

        $sendStatus = $this->node->get('send_status');
        if ($sendStatus !== NotificationSendStatus::SENT && $sendStatus !== NotificationSendStatus::FAILED) {
            $this->node->set('send_status', NotificationSendStatus::CANCELED);
        }
    }

    protected function applyNotificationFailed(Message $event): void
    {
        $this->node
            ->set('send_status', NotificationSendStatus::FAILED)
            ->clear('sent_at')
            ->set('notifier_result', $event->get('notifier_result'));
    }

    protected function applyNotificationSent(Message $event): void
    {
        $this->node
            ->set('send_status', NotificationSendStatus::SENT)
            ->set('sent_at', $event->get('occurred_at')->toDateTime())
            ->set('notifier_result', $event->get('notifier_result'));
    }

    protected function enrichNodeCreated(Message $event): void
    {
        /** @var Message $node */
        $node = $event->get('node');
        $node
            ->set('status', NodeStatus::PUBLISHED)
            ->clear('sent_at');

        parent::enrichNodeCreated($event);
    }

    protected function enrichNodeUpdated(Message $event): void
    {
        /** @var Message $oldNode */
        $oldNode = $event->get('old_node');

        /** @var Message $newNode */
        $newNode = $event->get('new_node');

        $newNode
            // app_ref SHOULD NOT change during an update
            ->set('app_ref', $oldNode->get('app_ref'))
            // content_ref SHOULD NOT change during an update
            ->set('content_ref', $oldNode->get('content_ref'));

        // notifications are only published or deleted, enforce it.
        if (NodeStatus::DELETED->value !== $newNode->fget('status')) {
            $newNode->set('status', NodeStatus::PUBLISHED);
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
    protected function createNotificationFailed(Message $command): Message
    {
        return MessageResolver::resolveCurie('*:notify:event:notification-failed:v1')::create();
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
    protected function createNotificationSent(Message $command): Message
    {
        return MessageResolver::resolveCurie('*:notify:event:notification-sent:v1')::create();
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
        return MessageResolver::resolveCurie('*:notify:event:notification-created:v1')::create();
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
        return MessageResolver::resolveCurie('*:notify:event:notification-deleted:v1')::create();
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
