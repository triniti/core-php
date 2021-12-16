<?php
declare(strict_types=1);

namespace Triniti\Apollo;

use Gdbots\Ncr\Aggregate;
use Gdbots\Pbj\Message;
use Gdbots\Pbj\MessageResolver;

class PollAggregate extends Aggregate
{
    /**
     * This is for legacy uses of command/event mixins for common
     * ncr operations. It will be removed in 4.x.
     *
     * @param string $name
     * @param array  $arguments
     *
     * @return mixed
     */
    public function __call(string $name, array $arguments)
    {
        $newName = str_replace('Poll', 'Node', $name);
        if ($newName !== $name && is_callable([$this, $newName])) {
            return $this->$newName(...$arguments);
        }
    }

    /**
     * This is for legacy uses of command/event mixins for common
     * ncr operations. It will be removed in 4.x.
     *
     * @param Message $command
     *
     * @return Message
     *
     * @deprecated Will be removed in 4.x.
     */
    protected function createNodeCreatedEvent(Message $command): Message
    {
        return MessageResolver::resolveCurie('*:apollo:event:poll-created:v1')::create();
    }

    /**
     * This is for legacy uses of command/event mixins for common
     * ncr operations. It will be removed in 4.x.
     *
     * @param Message $command
     *
     * @return Message
     *
     * @deprecated Will be removed in 4.x.
     */
    protected function createNodeDeletedEvent(Message $command): Message
    {
        return MessageResolver::resolveCurie('*:apollo:event:poll-deleted:v1')::create();
    }

    /**
     * This is for legacy uses of command/event mixins for common
     * ncr operations. It will be removed in 4.x.
     *
     * @param Message $command
     *
     * @return Message
     *
     * @deprecated Will be removed in 4.x.
     */
    protected function createNodeExpiredEvent(Message $command): Message
    {
        return MessageResolver::resolveCurie('*:apollo:event:poll-expired:v1')::create();
    }

    /**
     * This is for legacy uses of command/event mixins for common
     * ncr operations. It will be removed in 4.x.
     *
     * @param Message $command
     *
     * @return Message
     *
     * @deprecated Will be removed in 4.x.
     */
    protected function createNodeLockedEvent(Message $command): Message
    {
        return MessageResolver::resolveCurie('*:apollo:event:poll-locked:v1')::create();
    }

    /**
     * This is for legacy uses of command/event mixins for common
     * ncr operations. It will be removed in 4.x.
     *
     * @param Message $command
     *
     * @return Message
     *
     * @deprecated Will be removed in 4.x.
     */
    protected function createNodeMarkedAsDraftEvent(Message $command): Message
    {
        return MessageResolver::resolveCurie('*:apollo:event:poll-marked-as-draft:v1')::create();
    }

    /**
     * This is for legacy uses of command/event mixins for common
     * ncr operations. It will be removed in 4.x.
     *
     * @param Message $command
     *
     * @return Message
     *
     * @deprecated Will be removed in 4.x.
     */
    protected function createNodeMarkedAsPendingEvent(Message $command): Message
    {
        return MessageResolver::resolveCurie('*:apollo:event:poll-marked-as-pending:v1')::create();
    }

    /**
     * This is for legacy uses of command/event mixins for common
     * ncr operations. It will be removed in 4.x.
     *
     * @param Message $command
     *
     * @return Message
     *
     * @deprecated Will be removed in 4.x.
     */
    protected function createNodePublishedEvent(Message $command): Message
    {
        return MessageResolver::resolveCurie('*:apollo:event:poll-published:v1')::create();
    }

    /**
     * This is for legacy uses of command/event mixins for common
     * ncr operations. It will be removed in 4.x.
     *
     * @param Message $command
     *
     * @return Message
     *
     * @deprecated Will be removed in 4.x.
     */
    protected function createNodeRenamedEvent(Message $command): Message
    {
        return MessageResolver::resolveCurie('*:apollo:event:poll-renamed:v1')::create();
    }

    /**
     * This is for legacy uses of command/event mixins for common
     * ncr operations. It will be removed in 4.x.
     *
     * @param Message $command
     *
     * @return Message
     *
     * @deprecated Will be removed in 4.x.
     */
    protected function createNodeScheduledEvent(Message $command): Message
    {
        return MessageResolver::resolveCurie('*:apollo:event:poll-scheduled:v1')::create();
    }

    /**
     * This is for legacy uses of command/event mixins for common
     * ncr operations. It will be removed in 4.x.
     *
     * @param Message $command
     *
     * @return Message
     *
     * @deprecated Will be removed in 4.x.
     */
    protected function createNodeUnlockedEvent(Message $command): Message
    {
        return MessageResolver::resolveCurie('*:apollo:event:poll-unlocked:v1')::create();
    }

    /**
     * This is for legacy uses of command/event mixins for common
     * ncr operations. It will be removed in 4.x.
     *
     * @param Message $command
     *
     * @return Message
     *
     * @deprecated Will be removed in 4.x.
     */
    protected function createNodeUnpublishedEvent(Message $command): Message
    {
        return MessageResolver::resolveCurie('*:apollo:event:poll-unpublished:v1')::create();
    }

    /**
     * This is for legacy uses of command/event mixins for common
     * ncr operations. It will be removed in 4.x.
     *
     * @param Message $command
     *
     * @return Message
     *
     * @deprecated Will be removed in 4.x.
     */
    protected function createNodeUpdatedEvent(Message $command): Message
    {
        return MessageResolver::resolveCurie('*:apollo:event:poll-updated:v1')::create();
    }
}
