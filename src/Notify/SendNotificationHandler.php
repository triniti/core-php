<?php
declare(strict_types=1);

namespace Triniti\Notify;

use Gdbots\Ncr\AggregateResolver;
use Gdbots\Ncr\Exception\NodeNotFound;
use Gdbots\Ncr\Ncr;
use Gdbots\Pbj\Message;
use Gdbots\Pbj\MessageResolver;
use Gdbots\Pbj\WellKnown\NodeRef;
use Gdbots\Pbjx\CommandHandler;
use Gdbots\Pbjx\Pbjx;
use Gdbots\Schemas\Ncr\Enum\NodeStatus;
use Gdbots\Schemas\Pbjx\Enum\Code;
use Triniti\Schemas\Notify\Enum\NotificationSendStatus;
use Triniti\Schemas\Notify\NotifierResultV1;

class SendNotificationHandler implements CommandHandler
{
    protected Ncr $ncr;
    protected NotifierLocator $locator;

    public static function handlesCuries(): array
    {
        // deprecated mixins, will be removed in 3.x
        $curies = MessageResolver::findAllUsingMixin('triniti:notify:mixin:send-notification:v1', false);
        $curies[] = 'triniti:notify:command:send-notification';
        return $curies;
    }

    public function __construct(Ncr $ncr, NotifierLocator $locator)
    {
        $this->ncr = $ncr;
        $this->locator = $locator;
    }

    public function handleCommand(Message $command, Pbjx $pbjx): void
    {
        /** @var NodeRef $nodeRef */
        $nodeRef = $command->get('node_ref');
        $context = ['causator' => $command];

        try {
            $notification = $this->ncr->getNode($nodeRef, true, $context);
        } catch (NodeNotFound $nf) {
            // doesn't exist, ignore
            return;
        } catch (\Throwable $e) {
            throw $e;
        }

        /** @var NotificationAggregate $aggregate */
        $aggregate = AggregateResolver::resolve($nodeRef->getQName())::fromNode($notification, $pbjx);
        $aggregate->sync($context);
        $notification = $aggregate->getNode();

        if (NotificationSendStatus::SCHEDULED !== $notification->fget('send_status')) {
            return;
        }

        /** @var NodeRef $appRef */
        $appRef = $notification->get('app_ref');

        /** @var NodeRef $contentRef */
        $contentRef = $notification->get('content_ref');

        $refs = [$appRef];
        if (null !== $contentRef) {
            $refs[] = $contentRef;
        }

        $nodes = $this->ncr->getNodes($refs, true, $context);
        $app = $nodes[$appRef->toString()] ?? null;
        $content = null !== $contentRef ? ($nodes[$contentRef->toString()] ?? null) : null;
        $result = null;

        if (
            null === $app
            || !$app::schema()->hasMixin('gdbots:iam:mixin:app')
            || !NotificationValidator::isSupportedByApp($nodeRef->getQName(), $appRef)
        ) {
            $result = NotifierResultV1::create()
                ->set('ok', false)
                ->set('code', Code::NOT_FOUND)
                ->set('error_name', 'NodeNotFound')
                ->set('error_message', "App [{$appRef}] was not found.");
        }

        if (null === $result && null !== $contentRef) {
            if (null === $content || !$content::schema()->hasMixin('triniti:notify:mixin:has-notifications')) {
                $result = NotifierResultV1::create()
                    ->set('ok', false)
                    ->set('code', Code::INVALID_ARGUMENT)
                    ->set('error_name', 'InvalidNotificationContent')
                    ->set('error_message', "Selected content [{$contentRef}] does not support notifications.");
            } elseif (NodeStatus::PUBLISHED !== $content->fget('status')
                && 'delete' !== $notification->get('apple_news_operation')
            ) {
                // If notification is an apple news delete operation then it's ok the send
                // notification against and unpublished article
                $result = NotifierResultV1::create()
                    ->set('ok', false)
                    ->set('code', Code::ABORTED)
                    ->set('error_name', 'UnpublishedNotificationContent')
                    ->set('error_message', "Selected content [{$contentRef}] is [{$content->fget('status')}].");
            }
        }

        if (null === $result) {
            $notifier = $this->locator->getNotifier($notification::schema()->getCurie());
            $result = $notifier->send($notification, $app, $content);
        }

        $retryCodes = [
            Code::ABORTED            => true,
            Code::DEADLINE_EXCEEDED  => true,
            Code::INTERNAL           => true,
            Code::RESOURCE_EXHAUSTED => true,
            Code::UNAVAILABLE        => true,
            Code::UNKNOWN            => true,
        ];

        if (
            !$result->get('ok')
            && $command->get('ctx_retries') < 3
            && isset($retryCodes[$result->get('code')])
        ) {
            // reschedule notification if service is down.
            $newCommand = clone $command;
            $retries = $newCommand->get('ctx_retries') + 1;
            $timestamp = strtotime('+' . (120 * $retries) . ' seconds');
            $newCommand->set('ctx_retries', $retries);
            $pbjx->copyContext($command, $newCommand);
            $pbjx->sendAt($newCommand, $timestamp, "{$nodeRef}.send");
            return;
        }

        if ($result->get('ok')) {
            $aggregate->onNotificationSent($command, $result);
        } else {
            $aggregate->onNotificationFailed($command, $result);
        }

        $aggregate->commit($context);
    }
}
