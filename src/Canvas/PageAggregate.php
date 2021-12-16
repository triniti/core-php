<?php
declare(strict_types=1);

namespace Triniti\Canvas;

use Gdbots\Ncr\Aggregate;
use Gdbots\Pbj\Message;
use Gdbots\Pbj\MessageResolver;

class PageAggregate extends Aggregate
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
        $newName = str_replace('Page', 'Node', $name);
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
        return MessageResolver::resolveCurie('*:canvas:event:page-created:v1')::create();
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
        return MessageResolver::resolveCurie('*:canvas:event:page-deleted:v1')::create();
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
        return MessageResolver::resolveCurie('*:canvas:event:page-expired:v1')::create();
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
        return MessageResolver::resolveCurie('*:canvas:event:page-locked:v1')::create();
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
        return MessageResolver::resolveCurie('*:canvas:event:page-marked-as-draft:v1')::create();
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
        return MessageResolver::resolveCurie('*:canvas:event:page-marked-as-pending:v1')::create();
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
        return MessageResolver::resolveCurie('*:canvas:event:page-published:v1')::create();
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
        return MessageResolver::resolveCurie('*:canvas:event:page-renamed:v1')::create();
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
        return MessageResolver::resolveCurie('*:canvas:event:page-scheduled:v1')::create();
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
        return MessageResolver::resolveCurie('*:canvas:event:page-unlocked:v1')::create();
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
        return MessageResolver::resolveCurie('*:canvas:event:page-unpublished:v1')::create();
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
        return MessageResolver::resolveCurie('*:canvas:event:page-updated:v1')::create();
    }
}
