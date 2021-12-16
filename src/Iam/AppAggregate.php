<?php
declare(strict_types=1);

namespace Triniti\Iam;

use Gdbots\Iam\AppAggregate as BaseAppAggregate;
use Gdbots\Pbj\Message;
use Gdbots\Pbj\MessageResolver;

class AppAggregate extends BaseAppAggregate
{
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
    protected function createAppRolesGranted(Message $command): Message
    {
        return MessageResolver::resolveCurie('*:iam:event:app-roles-granted:v1')::create();
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
    protected function createAppRolesRevoked(Message $command): Message
    {
        return MessageResolver::resolveCurie('*:iam:event:app-roles-revoked:v1')::create();
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
        return MessageResolver::resolveCurie('*:iam:event:app-created:v1')::create();
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
        return MessageResolver::resolveCurie('*:iam:event:app-deleted:v1')::create();
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
        return MessageResolver::resolveCurie('*:iam:event:app-expired:v1')::create();
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
        return MessageResolver::resolveCurie('*:iam:event:app-locked:v1')::create();
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
        return MessageResolver::resolveCurie('*:iam:event:app-marked-as-draft:v1')::create();
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
        return MessageResolver::resolveCurie('*:iam:event:app-marked-as-pending:v1')::create();
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
        return MessageResolver::resolveCurie('*:iam:event:app-published:v1')::create();
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
        return MessageResolver::resolveCurie('*:iam:event:app-renamed:v1')::create();
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
        return MessageResolver::resolveCurie('*:iam:event:app-scheduled:v1')::create();
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
        return MessageResolver::resolveCurie('*:iam:event:app-unlocked:v1')::create();
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
        return MessageResolver::resolveCurie('*:iam:event:app-unpublished:v1')::create();
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
        return MessageResolver::resolveCurie('*:iam:event:app-updated:v1')::create();
    }
}

