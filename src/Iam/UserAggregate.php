<?php
declare(strict_types=1);

namespace Triniti\Iam;

use Gdbots\Iam\UserAggregate as BaseUserAggregate;
use Gdbots\Pbj\Message;
use Gdbots\Pbj\MessageResolver;

class UserAggregate extends BaseUserAggregate
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
    protected function createUserRolesGranted(Message $command): Message
    {
        return MessageResolver::resolveCurie('*:iam:event:user-roles-granted:v1')::create();
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
    protected function createUserRolesRevoked(Message $command): Message
    {
        return MessageResolver::resolveCurie('*:iam:event:user-roles-revoked:v1')::create();
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
        return MessageResolver::resolveCurie('*:iam:event:user-created:v1')::create();
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
        return MessageResolver::resolveCurie('*:iam:event:user-deleted:v1')::create();
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
        return MessageResolver::resolveCurie('*:iam:event:user-expired:v1')::create();
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
        return MessageResolver::resolveCurie('*:iam:event:user-locked:v1')::create();
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
        return MessageResolver::resolveCurie('*:iam:event:user-marked-as-draft:v1')::create();
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
        return MessageResolver::resolveCurie('*:iam:event:user-marked-as-pending:v1')::create();
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
        return MessageResolver::resolveCurie('*:iam:event:user-published:v1')::create();
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
        return MessageResolver::resolveCurie('*:iam:event:user-renamed:v1')::create();
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
        return MessageResolver::resolveCurie('*:iam:event:user-scheduled:v1')::create();
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
        return MessageResolver::resolveCurie('*:iam:event:user-unlocked:v1')::create();
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
        return MessageResolver::resolveCurie('*:iam:event:user-unpublished:v1')::create();
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
        return MessageResolver::resolveCurie('*:iam:event:user-updated:v1')::create();
    }
}

