<?php
declare(strict_types=1);

namespace Triniti\Notify;

use Gdbots\Ncr\Event\NodeProjectedEvent;
use Gdbots\Pbj\Message;
use Gdbots\Pbjx\EventSubscriber;
use Gdbots\Pbjx\Pbjx;
use Triniti\Schemas\Notify\Command\SendNotificationV1;
use Triniti\Schemas\Notify\Enum\NotificationSendStatus;

class NotificationWatcher implements EventSubscriber
{
    public static function getSubscribedEvents(): array
    {
        return [
            'triniti:notify:mixin:notification.created'             => 'schedule',
            'triniti:notify:mixin:notification.deleted'             => 'cancel',
            'triniti:notify:mixin:notification.updated'             => 'reschedule',
            'triniti:notify:mixin:notification.notification-failed' => 'cancel',
            'triniti:notify:mixin:notification.notification-sent'   => 'cancel',
        ];
    }

    public function cancel(NodeProjectedEvent $pbjxEvent): void
    {
        $event = $pbjxEvent->getLastEvent();
        if ($event->isReplay()) {
            return;
        }

        $nodeRef = $pbjxEvent->getNode()->generateNodeRef();
        $pbjxEvent::getPbjx()->cancelJobs(["{$nodeRef}.send"], ['causator' => $event]);
    }

    public function schedule(NodeProjectedEvent $pbjxEvent): void
    {
        $event = $pbjxEvent->getLastEvent();
        if ($event->isReplay()) {
            return;
        }

        $this->createJob($pbjxEvent->getNode(), $event, $pbjxEvent::getPbjx());
    }

    public function reschedule(NodeProjectedEvent $pbjxEvent): void
    {
        $event = $pbjxEvent->getLastEvent();
        if ($event->isReplay()) {
            return;
        }

        $pbjx = $pbjxEvent::getPbjx();
        $newNode = $pbjxEvent->getNode();
        /** @var Message $oldNode */
        $oldNode = $event->get('old_node');
        $nodeRef = $event->get('node_ref') ?: $newNode->generateNodeRef();
        $oldSendAt = $oldNode ? $oldNode->fget('send_at') : null;
        $newSendAt = $newNode->fget('send_at');

        if ($oldSendAt === $newSendAt) {
            return;
        }

        if (null === $newSendAt) {
            if (null !== $oldSendAt) {
                $pbjx->cancelJobs(["{$nodeRef}.send"], ['causator' => $event]);
            }
            return;
        }

        $this->createJob($newNode, $event, $pbjx);
    }

    protected function createJob(Message $node, Message $event, Pbjx $pbjx): void
    {
        if (!$node->has('send_at') || !$node->has('send_status')) {
            return;
        }

        if (NotificationSendStatus::SCHEDULED !== $node->fget('send_status')) {
            // only a scheduled notification should create a job
            return;
        }

        $nodeRef = $node->generateNodeRef();
        $command = SendNotificationV1::create()->set('node_ref', $nodeRef);

        /** @var \DateTimeInterface $sendAt */
        $sendAt = $node->get('send_at');

        $timestamp = $sendAt->getTimestamp();
        if ($timestamp <= (time() + 5)) {
            $timestamp = strtotime('+5 seconds');
        }

        $pbjx->copyContext($event, $command);
        $pbjx->sendAt($command, $timestamp, "{$nodeRef}.send", ['causator' => $event]);
    }
}
