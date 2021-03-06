<?php
declare(strict_types=1);

namespace Triniti\Notify;

use Gdbots\Pbj\Assertion;
use Gdbots\Pbj\Message;
use Gdbots\Pbj\SchemaQName;
use Gdbots\Pbj\WellKnown\NodeRef;
use Gdbots\Pbjx\DependencyInjection\PbjxValidator;
use Gdbots\Pbjx\Event\PbjxEvent;
use Gdbots\Pbjx\EventSubscriber;
use Triniti\Notify\Exception\NotificationAlreadyScheduled;
use Triniti\Notify\Exception\NotificationAlreadySent;
use Triniti\Schemas\Notify\Enum\NotificationSendStatus;
use Triniti\Schemas\Notify\Request\SearchNotificationsRequestV1;

class NotificationValidator implements EventSubscriber, PbjxValidator
{
    public static function getSubscribedEvents()
    {
        return [
            'triniti:notify:mixin:notification.validate' => 'validate',
        ];
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
    public static function isSupportedByApp(SchemaQName $qname, NodeRef $appRef): bool
    {
        $expected = str_replace('-notification', '-app', $qname->toString());
        return $appRef->getQName()->toString() === $expected;
    }

    public static function alreadySent(Message $notification): bool
    {
        $status = $notification->fget('send_status', NotificationSendStatus::DRAFT);
        $sent = [
            NotificationSendStatus::SENT     => true,
            NotificationSendStatus::FAILED   => true,
            NotificationSendStatus::CANCELED => true,
        ];

        return $sent[$status] ?? false;
    }

    public function validate(PbjxEvent $pbjxEvent): void
    {
        $event = $pbjxEvent->hasParentEvent() ? $pbjxEvent->getParentEvent() : $pbjxEvent;
        $method = $event->getMessage()::schema()->getHandlerMethodName(false, 'validate');
        if (is_callable([$this, $method])) {
            $this->$method($event);
        }
    }

    protected function validateCreateNotification(PbjxEvent $pbjxEvent): void
    {
        $this->validateCreateNode($pbjxEvent);
    }

    protected function validateCreateNode(PbjxEvent $pbjxEvent): void
    {
        $command = $pbjxEvent->getMessage();
        Assertion::true($command->has('node'), 'Field "node" is required.', 'node');

        /** @var Message $node */
        $node = $command->get('node');
        Assertion::true($node->has('app_ref'), 'Field "node.app_ref" is required.', 'node.app_ref');

        /** @var NodeRef $appRef */
        $appRef = $node->get('app_ref');
        $nodeRef = $node->generateNodeRef();

        Assertion::true(
            self::isSupportedByApp($nodeRef->getQName(), $appRef),
            sprintf(
                'The app [%s] does not support the [%s].',
                $appRef->toString(),
                $nodeRef->getQName()->toString()
            ),
            'node.app_ref'
        );

        if ($node->has('content_ref')) {
            $this->ensureNotAlreadyScheduled($pbjxEvent, $node);
        }
    }

    protected function validateUpdateNotification(PbjxEvent $pbjxEvent): void
    {
        $this->validateUpdateNode($pbjxEvent);
    }

    protected function validateUpdateNode(PbjxEvent $pbjxEvent): void
    {
        $command = $pbjxEvent->getMessage();
        Assertion::true($command->has('old_node'), 'Field "old_node" is required.', 'old_node');
        Assertion::true($command->has('new_node'), 'Field "new_node" is required.', 'new_node');

        /** @var Message $oldNode */
        $oldNode = $command->get('old_node');

        /** @var Message $newNode */
        $newNode = $command->get('new_node');
        Assertion::true($newNode->has('app_ref'), 'Field "new_node.app_ref" is required.', 'new_node.app_ref');

        /** @var NodeRef $appRef */
        $appRef = $newNode->get('app_ref');
        $nodeRef = $newNode->generateNodeRef();

        Assertion::true(
            self::isSupportedByApp($nodeRef->getQName(), $appRef),
            sprintf(
                'The app [%s] does not support the [%s].',
                $appRef->toString(),
                $nodeRef->getQName()->toString()
            ),
            'new_node.app_ref'
        );

        // An update SHOULD NOT change the send_status or sent_at
        $newNode->set('send_status', $oldNode->get('send_status'));
        $newNode->set('sent_at', $oldNode->get('sent_at'));

        // we trust the old node here because the server binds it
        // at the start of the request (ref NodeCommandBinder)
        if (self::alreadySent($oldNode)) {
            throw new NotificationAlreadySent();
        }
    }

    protected function ensureNotAlreadyScheduled(PbjxEvent $event, Message $notification): void
    {
        /** @var NodeRef $appRef */
        $appRef = $notification->get('app_ref');

        /** @var NodeRef $contentRef */
        $contentRef = $notification->get('content_ref');

        $request = SearchNotificationsRequestV1::create()
            ->set('app_ref', $appRef)
            ->set('content_ref', $contentRef)
            ->set('send_status', NotificationSendStatus::SCHEDULED())
            ->set('count', 1);

        $response = $event::getPbjx()->copyContext($event->getMessage(), $request)->request($request);

        if ($response->has('nodes')) {
            throw new NotificationAlreadyScheduled();
        }
    }
}
