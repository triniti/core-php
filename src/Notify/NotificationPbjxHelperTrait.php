<?php
declare(strict_types=1);

namespace Triniti\Notify;

use Gdbots\Ncr\Ncr;
use Gdbots\Pbj\Message;
use Gdbots\Pbj\SchemaQName;
use Gdbots\Pbj\WellKnown\NodeRef;
use Triniti\Schemas\Notify\Enum\NotificationSendStatus;

trait NotificationPbjxHelperTrait
{
    protected Ncr $ncr;

    protected function isNodeSupported(Message $node): bool
    {
        return $node::schema()->hasMixin('triniti:notify:mixin:notification');
    }

    /**
     * When a notification is created/updated we must ensure the app
     * it is bound to supports this type of notification. In all cases
     * so far this is a one to one, e.g. alexa-notification can only
     * be sent by an alexa-app. By convention both apps and notifications
     * are named (the node type) using those matching suffixes.
     *
     * @param SchemaQName $qname
     * @param NodeRef     $appRef
     *
     * @return bool
     */
    protected function isSupportedByApp(SchemaQName $qname, NodeRef $appRef): bool
    {
        $expected = str_replace('-notification', '-app', $qname->toString());
        return $appRef->getQName()->toString() === $expected;
    }

    /**
     * @param Message $notification
     *
     * @return bool
     */
    protected function alreadySent(Message $notification): bool
    {
        /** @var NotificationSendStatus $status */
        $status = $notification->get('send_status', NotificationSendStatus::DRAFT());

        if (
            $status->equals(NotificationSendStatus::SENT())
            || $status->equals(NotificationSendStatus::FAILED())
            || $status->equals(NotificationSendStatus::CANCELED())
        ) {
            return true;
        }

        return false;
    }
}
