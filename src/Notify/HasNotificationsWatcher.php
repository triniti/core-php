<?php
declare(strict_types=1);

namespace Triniti\Notify;

use Gdbots\Ncr\Event\NodeProjectedEvent;
use Gdbots\Pbj\Message;
use Gdbots\Pbj\Util\ClassUtil;
use Gdbots\Pbjx\EventSubscriber;
use Gdbots\Pbjx\Pbjx;
use Gdbots\Schemas\Ncr\Command\DeleteNodeV1;
use Gdbots\Schemas\Ncr\Command\UpdateNodeV1;
use Psr\Log\LoggerInterface;
use Triniti\Schemas\Notify\Enum\NotificationSendStatus;
use Triniti\Schemas\Notify\Enum\SearchNotificationsSort;
use Triniti\Schemas\Notify\Request\SearchNotificationsRequestV1;

/**
 * Responsible for watching changes to nodes that have
 * the mixin "triniti:notify:mixin:has-notifications" and
 * keeping their associated notifications up to date.
 */
class HasNotificationsWatcher implements EventSubscriber
{
    protected LoggerInterface $logger;

    public static function getSubscribedEvents(): array
    {
        return [
            'triniti:notify:mixin:has-notifications.deleted'     => 'cancel',
            'triniti:notify:mixin:has-notifications.expired'     => 'cancel',
            'triniti:notify:mixin:has-notifications.published'   => 'schedule',
            'triniti:notify:mixin:has-notifications.scheduled'   => 'schedule',
            'triniti:notify:mixin:has-notifications.unpublished' => 'cancel',
            'triniti:notify:mixin:has-notifications.updated'     => 'schedule',
        ];
    }

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function cancel(NodeProjectedEvent $pbjxEvent): void
    {
        $event = $pbjxEvent->getLastEvent();
        $pbjx = $pbjxEvent::getPbjx();
        if ($event->isReplay()) {
            return;
        }

        $contentRef = $pbjxEvent->getNode()->generateNodeRef();
        $request = $this->createSearchNotificationsRequest($event, $pbjx)->set('content_ref', $contentRef);

        $this->forEachNotification($request, $pbjx, function (Message $node) use ($event, $pbjx) {
            $command = DeleteNodeV1::create()->set('node_ref', $node->generateNodeRef());
            $pbjx->copyContext($event, $command);
            return $command;
        });
    }

    public function schedule(NodeProjectedEvent $pbjxEvent): void
    {
        $event = $pbjxEvent->getLastEvent();
        $pbjx = $pbjxEvent::getPbjx();
        if ($event->isReplay()) {
            return;
        }

        $content = $pbjxEvent->getNode();
        $contentRef = $content->generateNodeRef();
        $sendAt = null;

        if ($content->has('published_at')) {
            $sendAt = clone $content->get('published_at');
            $sendAt = $sendAt->modify('+10 seconds');
        }

        $request = $this->createSearchNotificationsRequest($event, $pbjx);
        $request
            ->set('content_ref', $contentRef)
            ->set('q', $request->get('q') . ' +send_on_publish:true');

        $this->forEachNotification($request, $pbjx, function (Message $node) use ($sendAt, $content, $event, $pbjx) {
            $newNode = clone $node;
            $newNode
                ->set('title', $content->get('title'))
                ->set('send_at', $sendAt);

            if ($newNode->has('send_at')) {
                $newNode->set('send_status', NotificationSendStatus::SCHEDULED);
            } else {
                $newNode->set('send_status', NotificationSendStatus::DRAFT);
            }

            $old = serialize([
                $node->get('send_at'),
                $node->get('send_status'),
                $node->get('title'),
            ]);

            $new = serialize([
                $newNode->get('send_at'),
                $newNode->get('send_status'),
                $newNode->get('title'),
            ]);

            if ($old === $new) {
                return null;
            }

            $command = UpdateNodeV1::create()
                ->set('node_ref', $newNode->generateNodeRef())
                ->set('new_node', $newNode)
                ->addToSet('paths', ['send_at', 'send_status', 'title']);
            $pbjx->copyContext($event, $command);
            return $command;
        });
    }

    /**
     * Finds all notifications for the given request and executes the
     * factory with the node to create a command. The command is then
     * sent via pbjx unless it is null, then it is ignored.
     *
     * @param Message  $request
     * @param Pbjx     $pbjx
     * @param callable $factory
     */
    public function forEachNotification(Message $request, Pbjx $pbjx, callable $factory): void
    {
        $lastDate = new \DateTime('100 years ago');

        do {
            $response = $pbjx->request($request);

            /** @var Message $node */
            foreach ($response->get('nodes', []) as $index => $node) {
                /*
                 * If apple news notification and apple_news_operation is not notification then ignore.
                 * This is because the AppleNewsWatcher is the one handling the notifications to apple
                 * for create, update, delete and this watcher is responsible for more traditional
                 * notifications like alerts, emails, etc.
                 */
                if (
                    $node::schema()->hasMixin('triniti:notify:mixin:apple-news-notification')
                    && 'notification' !== $node->get('apple_news_operation')
                ) {
                    continue;
                }

                $lastDate = $node->get('created_at')->toDateTime();
                /** @var Message $command */
                $command = $factory($node);

                if (null === $command) {
                    continue;
                }

                try {
                    $timestamp = strtotime(sprintf('+%d seconds', (2 + $index)));
                    $pbjx->sendAt($command, $timestamp, "{$node->generateNodeRef()}.sync");
                } catch (\Throwable $e) {
                    $this->logger->error(
                        sprintf('%s [{pbj_schema}] failed to send.', ClassUtil::getShortName($e)),
                        [
                            'exception'  => $e,
                            'pbj_schema' => $command::schema()->getId()->toString(),
                            'pbj'        => $command->toArray(),
                        ]
                    );
                }
            }

            $request = clone $request;
            $request->set('created_after', $lastDate);
        } while ($response->get('has_more'));
    }

    protected function createSearchNotificationsRequest(Message $event, Pbjx $pbjx): Message
    {
        $request = SearchNotificationsRequestV1::create();
        $pbjx->copyContext($event, $request);
        return $request
            ->set('q', sprintf(
                '+send_status:(%s OR %s)',
                NotificationSendStatus::DRAFT,
                NotificationSendStatus::SCHEDULED
            ))
            ->set('sort', SearchNotificationsSort::CREATED_AT_ASC())
            ->set('count', 255);
    }
}
