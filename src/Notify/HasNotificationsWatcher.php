<?php
declare(strict_types=1);

namespace Triniti\Notify;

use Gdbots\Ncr\Ncr;
use Gdbots\Ncr\NcrSearch;
use Gdbots\Pbj\Message;
use Gdbots\Pbj\MessageResolver;
use Gdbots\Pbj\SchemaCurie;
use Gdbots\Pbj\Util\ClassUtil;
use Gdbots\Pbj\WellKnown\NodeRef;
use Gdbots\Pbjx\EventSubscriber;
use Gdbots\Pbjx\Pbjx;
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
    protected Ncr $ncr;
    protected NcrSearch $ncrSearch;
    protected LoggerInterface $logger;

    public function __construct(Ncr $ncr, NcrSearch $ncrSearch, LoggerInterface $logger)
    {
        $this->ncr = $ncr;
        $this->ncrSearch = $ncrSearch;
        $this->logger = $logger;
    }

    public static function getSubscribedEvents()
    {
        return [
            'gdbots:ncr:mixin:node-deleted'     => 'onNodeDeleted',
            'gdbots:ncr:mixin:node-expired'     => 'onNodeExpired',
            'gdbots:ncr:mixin:node-published'   => 'onNodePublished',
            'gdbots:ncr:mixin:node-scheduled'   => 'onNodeScheduled',
            'gdbots:ncr:mixin:node-updated'     => 'onNodeUpdated',
            'gdbots:ncr:mixin:node-unpublished' => 'onNodeUnpublished',
        ];
    }

    public function onNodeDeleted(Message $event, Pbjx $pbjx): void
    {
        $this->cancelNotification($event, $pbjx);
    }

    public function onNodeExpired(Message $event, Pbjx $pbjx): void
    {
        $this->cancelNotification($event, $pbjx);
    }

    public function onNodePublished(Message $event, Pbjx $pbjx): void
    {
        /** @var NodeRef $contentRef */
        $contentRef = $event->get('node_ref');
        $this->scheduleNotification($event, $pbjx, $contentRef, $event->get('published_at'));
    }

    public function onNodeScheduled(Message $event, Pbjx $pbjx): void
    {
        /** @var NodeRef $contentRef */
        $contentRef = $event->get('node_ref');
        $this->scheduleNotification($event, $pbjx, $contentRef, $event->get('publish_at'));
    }

    public function onNodeUpdated(Message $event, Pbjx $pbjx): void
    {
        /** @var Message $content */
        $content = $event->get('new_node');
        if (null === $content || !$this->isNodeSupported($content)) {
            return;
        }

        $this->scheduleNotification(
            $event,
            $pbjx,
            NodeRef::fromNode($content),
            $content->get('published_at'),
            $content->get('title')
        );
    }

    public function onNodeUnpublished(Message $event, Pbjx $pbjx): void
    {
        $this->cancelNotification($event, $pbjx);
    }

    protected function isNodeSupported(Message $node): bool
    {
        return $node::schema()->hasMixin('triniti:notify:mixin:has-notifications');
    }

    protected function isNodeRefSupported(NodeRef $nodeRef): bool
    {
        static $validQNames = null;
        if (null === $validQNames) {
            $validQNames = [];
            foreach (MessageResolver::findAllUsingMixin('triniti:notify:mixin:has-notifications', false) as $curie) {
                $qname = SchemaCurie::fromString($curie)->getQName();
                $validQNames[$qname->toString()] = true;
            }
        }

        return isset($validQNames[$nodeRef->getQName()->toString()]);
    }

    protected function cancelNotification(Message $event, Pbjx $pbjx): void
    {
        if ($event->isReplay()) {
            return;
        }

        /** @var NodeRef $contentRef */
        $contentRef = $event->get('node_ref');
        if (null === $contentRef || !$this->isNodeRefSupported($contentRef)) {
            return;
        }

        $request = $this->createSearchNotificationsRequest($event, $pbjx)->set('content_ref', $contentRef);
        $this->forEachNotification($request, $pbjx, function (Message $node) use ($event, $pbjx) {
            return $this->createDeleteNotification($node, $event, $pbjx);
        });
    }

    public function scheduleNotification(
        Message $event,
        Pbjx $pbjx,
        NodeRef $contentRef,
        ?\DateTimeInterface $sendAt = null,
        ?string $title = null
    ): void {
        if ($event->isReplay()) {
            return;
        }

        if (!$this->isNodeRefSupported($contentRef)) {
            return;
        }

        if (null !== $sendAt) {
            $sendAt = clone $sendAt;
            $sendAt = $sendAt->modify('+10 seconds');
        }

        $request = $this->createSearchNotificationsRequest($event, $pbjx);
        $request
            ->set('content_ref', $contentRef)
            ->set('q', $request->get('q') . ' +send_on_publish:true');

        $this->forEachNotification($request, $pbjx, function (Message $node)
        use ($sendAt, $title, $event, $pbjx) {
            $newNode = clone $node;
            $command = $this->createUpdateNotification($newNode, $event, $pbjx);
            $newNode->set('send_at', $sendAt);
            if ($newNode->has('send_at')) {
                $newNode->set('send_status', NotificationSendStatus::SCHEDULED());
            } else {
                $newNode->set('send_status', NotificationSendStatus::DRAFT());
            }

            if (null !== $title) {
                $newNode->set('title', $title);
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

            return $old === $new ? null : $command;
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
            foreach ($response->get('nodes', []) as $node) {
                /*
                 * If apple news notification and apple_news_operation is not notification then ignore.
                 * This is because the AppleNewsWatcher in triniti/news is the one handling the notifications
                 * to apple for create, update, delete and this watcher is responsible for more traditional
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
                    $pbjx->send($command);
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

    protected function createDeleteNotification(Message $notification, Message $event, Pbjx $pbjx): Message
    {
        $vendor = MessageResolver::getDefaultVendor();
        $class = MessageResolver::resolveCurie(
            SchemaCurie::fromString("{$vendor}:notify:command:delete-notification")
        );

        $command = $class::create()->set('node_ref', NodeRef::fromNode($notification));
        $pbjx->copyContext($event, $command);
        return $command->set('ctx_correlator_ref', $event->generateMessageRef());
    }

    protected function createUpdateNotification(Message $notification, Message $event, Pbjx $pbjx): Message
    {
        $vendor = MessageResolver::getDefaultVendor();
        $class = MessageResolver::resolveCurie(
            SchemaCurie::fromString("{$vendor}:notify:command:update-notification")
        );

        $command = $class::create()
            ->set('node_ref', NodeRef::fromNode($notification))
            ->set('new_node', $notification);
        $pbjx->copyContext($event, $command);
        return $command->set('ctx_correlator_ref', $event->generateMessageRef());
    }
}
