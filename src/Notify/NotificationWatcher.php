<?php
declare(strict_types=1);

namespace Triniti\Notify;

use Gdbots\Pbj\Message;
use Gdbots\Pbj\MessageResolver;
use Gdbots\Pbj\WellKnown\NodeRef;
use Gdbots\Pbjx\EventSubscriber;
use Gdbots\Pbjx\Pbjx;
use Triniti\Schemas\Notify\Command\SendNotificationV1;
use Triniti\Schemas\Notify\Enum\NotificationSendStatus;

class NotificationWatcher implements EventSubscriber
{
    public static function getSubscribedEvents()
    {
        $vendor = MessageResolver::getDefaultVendor();
        return [
            "${vendor}:notify:event:notification-created" => 'onNotificationCreated',
            "${vendor}:notify:event:notification-deleted" => 'onNotificationDeleted',
            "${vendor}:notify:event:notification-updated" => 'onNotificationUpdated',
        ];
    }

    public function onNotificationCreated(Message $event, Pbjx $pbjx): void
    {
        if ($event->isReplay()) {
            return;
        }

        $this->createSendNotificationJob($event->get('node'), $event, $pbjx);
    }

    public function onNotificationDeleted(Message $event, Pbjx $pbjx): void
    {
        if ($event->isReplay()) {
            return;
        }

        /** @var NodeRef $nodeRef */
        $nodeRef = $event->get('node_ref');
        $pbjx->cancelJobs(["{$nodeRef}.send"]);
    }

    public function onNotificationUpdated(Message $event, Pbjx $pbjx): void
    {
        if ($event->isReplay()) {
            return;
        }

        /** @var Message $oldNode */
        $oldNode = $event->get('old_node');

        /** @var Message $newNode */
        $newNode = $event->get('new_node');

        $this->cancelOrCreateSendNotificationJob($newNode, $event, $pbjx, $oldNode);
    }

    protected function createSendNotificationJob(Message $node, Message $event, Pbjx $pbjx): void
    {
        if (!$node->has('send_at') || !$node->has('send_status')) {
            return;
        }

        if (!$node->get('send_status')->equals(NotificationSendStatus::SCHEDULED())) {
            // only a scheduled notification should create a job
            return;
        }

        /** @var NodeRef $nodeRef */
        $nodeRef = $event->get('node_ref') ?: NodeRef::fromNode($node);

        /** @var \DateTimeInterface $sendAt */
        $sendAt = $node->get('send_at');

        $command = SendNotificationV1::create()->set('node_ref', $nodeRef);
        $pbjx->copyContext($event, $command);
        $timestamp = $sendAt->getTimestamp();
        if ($timestamp <= (time() + 5)) {
            $timestamp = strtotime('+5 seconds');
        }

        $pbjx->sendAt($command, $timestamp, "{$nodeRef}.send");
    }

    protected function cancelOrCreateSendNotificationJob(
        Message $newNode,
        Message $event,
        Pbjx $pbjx,
        ?Message $oldNode = null
    ): void {
        /** @var NodeRef $nodeRef */
        $nodeRef = $event->get('node_ref') ?: NodeRef::fromNode($newNode);
        $sendAtField = $newNode::schema()->getField('send_at');

        /** @var \DateTimeInterface $oldSendAt */
        $oldSendAt = $oldNode ? $oldNode->get('send_at') : null;

        $oldSendAt = $sendAtField->getType()->encode($oldSendAt, $sendAtField);
        $newSendAt = $sendAtField->getType()->encode($newNode->get('send_at'), $sendAtField);

        if ($oldSendAt === $newSendAt) {
            return;
        }

        if (null === $newSendAt) {
            if (null !== $oldSendAt) {
                $pbjx->cancelJobs(["{$nodeRef}.send"]);
            }
            return;
        }

        $this->createSendNotificationJob($newNode, $event, $pbjx);
    }
}
