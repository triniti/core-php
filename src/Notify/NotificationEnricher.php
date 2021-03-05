<?php
declare(strict_types=1);

namespace Triniti\Notify;

use Gdbots\Ncr\Ncr;
use Gdbots\Pbj\Message;
use Gdbots\Pbj\WellKnown\NodeRef;
use Gdbots\Pbjx\DependencyInjection\PbjxEnricher;
use Gdbots\Pbjx\Event\PbjxEvent;
use Gdbots\Pbjx\EventSubscriber;
use Triniti\Notify\Exception\InvalidNotificationContent;
use Triniti\Schemas\Notify\Enum\NotificationSendStatus;

class NotificationEnricher implements EventSubscriber, PbjxEnricher
{
    protected Ncr $ncr;

    public static function getSubscribedEvents()
    {
        return [
            'triniti:notify:mixin:notification.enrich' => 'enrich',
        ];
    }

    public function __construct(Ncr $ncr)
    {
        $this->ncr = $ncr;
    }

    public function enrich(PbjxEvent $pbjxEvent): void
    {
        $event = $pbjxEvent->hasParentEvent() ? $pbjxEvent->getParentEvent() : $pbjxEvent;
        $method = $event->getMessage()::schema()->getHandlerMethodName(false, 'enrich');
        if (is_callable([$this, $method])) {
            $this->$method($event);
        }
    }

    protected function enrichNotificationCreated(PbjxEvent $pbjxEvent): void
    {
        $this->enrichNodeCreated($pbjxEvent);
    }

    protected function enrichNodeCreated(PbjxEvent $pbjxEvent): void
    {
        $event = $pbjxEvent->getMessage();

        /** @var Message $node */
        $node = $event->get('node');

        $this->applySchedule($pbjxEvent, $node);
    }

    protected function enrichNotificationUpdated(PbjxEvent $pbjxEvent): void
    {
        $this->enrichNodeUpdated($pbjxEvent);
    }

    protected function enrichNodeUpdated(PbjxEvent $pbjxEvent): void
    {
        $event = $pbjxEvent->getMessage();

        /** @var Message $newNode */
        $newNode = $event->get('new_node');

        $this->applySchedule($pbjxEvent, $newNode);
    }

    protected function applySchedule(PbjxEvent $pbjxEvent, Message $notification): void
    {
        if ($notification->isFrozen()) {
            return;
        }

        if (NotificationValidator::alreadySent($notification)) {
            // schedule cannot change at this point.
            return;
        }

        if (!$notification->has('send_at')
            && $notification->has('content_ref')
            && $notification->get('send_on_publish')
        ) {
            /** @var NodeRef $contentRef */
            $contentRef = $notification->get('content_ref');
            $content = $this->ncr->getNode($contentRef, true, ['causator' => $pbjxEvent->getMessage()]);

            if (!$content::schema()->hasMixin('triniti:notify:mixin:has-notifications')
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

        if ($notification->has('send_at')) {
            $notification->set('send_status', NotificationSendStatus::SCHEDULED());
        } else {
            $notification->set('send_status', NotificationSendStatus::DRAFT());
        }
    }
}
