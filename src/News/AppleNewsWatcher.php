<?php
declare(strict_types=1);

namespace Triniti\News;

use Gdbots\Ncr\AggregateResolver;
use Gdbots\Ncr\Event\NodeProjectedEvent;
use Gdbots\Ncr\Ncr;
use Gdbots\Pbj\Message;
use Gdbots\Pbj\MessageResolver;
use Gdbots\Pbj\WellKnown\NodeRef;
use Gdbots\Pbjx\EventSubscriber;
use Gdbots\Pbjx\Pbjx;
use Gdbots\Schemas\Iam\Request\SearchAppsRequestV1;
use Gdbots\Schemas\Ncr\Command\CreateNodeV1;
use Gdbots\Schemas\Ncr\Enum\NodeStatus;
use Gdbots\Schemas\Pbjx\Enum\Code;
use Triniti\Ncr\Search\Elastica\MappingBuilder;

class AppleNewsWatcher implements EventSubscriber
{
    protected Ncr $ncr;

    public static function getSubscribedEvents(): array
    {
        return [
            'triniti:news:mixin:article.deleted'                               => 'onArticleRemoval',
            'triniti:news:mixin:article.expired'                               => 'onArticleRemoval',
            'triniti:news:mixin:article.published'                             => 'onArticlePublished',
            'triniti:news:mixin:article.renamed'                               => 'onArticleRenamed',
            'triniti:news:mixin:article.unpublished'                           => 'onArticleRemoval',
            'triniti:news:mixin:article.updated'                               => 'onArticleUpdated',
            'triniti:notify:mixin:apple-news-notification.notification-failed' => 'onNotificationFailed',
            'triniti:notify:mixin:apple-news-notification.notification-sent'   => 'onNotificationSent',
        ];
    }

    public function __construct(Ncr $ncr)
    {
        $this->ncr = $ncr;
    }

    public function onArticlePublished(NodeProjectedEvent $pbjxEvent): void
    {
        $pbjx = $pbjxEvent::getPbjx();
        $event = $pbjxEvent->getLastEvent();
        $node = $pbjxEvent->getNode();
        $this->notifyAppleNews($event, $node, $pbjx, 'create');
    }

    public function onArticleRemoval(NodeProjectedEvent $pbjxEvent): void
    {
        $pbjx = $pbjxEvent::getPbjx();
        $event = $pbjxEvent->getLastEvent();
        $node = $pbjxEvent->getNode();
        $this->notifyAppleNews($event, $node, $pbjx, 'delete');
    }

    public function onArticleRenamed(NodeProjectedEvent $pbjxEvent): void
    {
        $pbjx = $pbjxEvent::getPbjx();
        $event = $pbjxEvent->getLastEvent();
        $node = $pbjxEvent->getNode();

        if (NodeStatus::PUBLISHED->value !== $node->fget('status')) {
            return;
        }

        $this->notifyAppleNews($event, $node, $pbjx, 'update');
    }

    public function onArticleUpdated(NodeProjectedEvent $pbjxEvent): void
    {
        $pbjx = $pbjxEvent::getPbjx();
        $event = $pbjxEvent->getLastEvent();
        $node = $pbjxEvent->getNode();

        if ($event->isReplay()) {
            return;
        }

        if ($event->get('old_etag') === $event->get('new_etag')) {
            return;
        }

        $oldNode = $event->get('old_node');
        $newNode = $node;

        if (NodeStatus::PUBLISHED->value !== $newNode->fget('status')) {
            if ($newNode->has('apple_news_id')) {
                $operation = 'delete';
            } else {
                return;
            }
        } elseif (null === $oldNode) {
            if (!$newNode->get('apple_news_enabled') && $newNode->has('apple_news_id')) {
                $operation = 'delete';
            } elseif ($newNode->get('apple_news_enabled')) {
                $operation = $newNode->has('apple_news_id') ? 'update' : 'create';
            } else {
                return;
            }
        } elseif ($oldNode->get('apple_news_enabled') && !$newNode->get('apple_news_enabled')) {
            $operation = 'delete';
        } elseif ($newNode->get('apple_news_enabled')) {
            $operation = $newNode->has('apple_news_id') ? 'update' : 'create';
        } else {
            return;
        }

        $this->notifyAppleNews($event, $newNode, $pbjx, $operation);
    }

    public function onNotificationFailed(NodeProjectedEvent $pbjxEvent): void
    {
        // todo: check why it failed and determine if a re-try is valid.
    }

    public function onNotificationSent(NodeProjectedEvent $pbjxEvent): void
    {
        $pbjx = $pbjxEvent::getPbjx();
        $event = $pbjxEvent->getLastEvent();
        $notification = $pbjxEvent->getNode();

        if ($event->isReplay()) {
            return;
        }

        if (!$notification->has('content_ref')) {
            return;
        }

        /** @var NodeRef $articleRef */
        $articleRef = $notification->get('content_ref');

        $context = ['causator' => $event];
        $article = $this->ncr->getNode($articleRef, true, $context);

        /** @var ArticleAggregate $aggregate */
        $aggregate = AggregateResolver::resolve($articleRef->getQName())::fromNode($article, $pbjx);
        $aggregate->sync($context);
        $aggregate->onAppleNewsNotificationSent($event, $notification);
        $aggregate->commit($context);
    }

    protected function createAppleNewsNotification(Message $event, Message $article, Pbjx $pbjx): Message
    {
        $date = $event->get('occurred_at')->toDateTime();
        return MessageResolver::resolveCurie('*:notify:node:apple-news-notification:v1')::create()
            ->set('title', $article->get('title') . ' ' . $date->format('Y-m-d\TH:i:s\Z'))
            ->set('send_at', $date)
            ->set('content_ref', $article->generateNodeRef())
            ->set('apple_news_id', $article->get('apple_news_id'))
            ->set('apple_news_revision', $article->get('apple_news_revision'));
    }

    protected function getApp(Message $article, Message $event, Pbjx $pbjx): ?Message
    {
        $typeField = MappingBuilder::TYPE_FIELD;
        $request = SearchAppsRequestV1::create()
            ->set('status', NodeStatus::PUBLISHED)
            ->set('q', "+{$typeField}:apple-news-app")
            ->set('count', 1);

        try {
            $response = $pbjx->copyContext($event, $request)->request($request);
            return $response->getFromListAt('nodes', 0);
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected function notifyAppleNews(Message $event, Message $article, Pbjx $pbjx, string $operation): void
    {
        if ($event->isReplay()) {
            return;
        }

        if ('delete' === $operation && !$article->has('apple_news_id')) {
            return;
        }

        if ('create' === $operation && !$article->get('apple_news_enabled')) {
            return;
        }

        if (!$this->shouldNotifyAppleNews($event, $article)) {
            return;
        }

        $app = $this->getApp($article, $event, $pbjx);
        if (null === $app) {
            return;
        }

        $notification = $this->createAppleNewsNotification($event, $article, $pbjx)
            ->set('app_ref', $app->generateNodeRef())
            ->set('apple_news_operation', $operation);

        if ('update' === $operation && !$article->has('apple_news_id')) {
            $notification
                ->set('apple_news_operation', 'create')
                ->clear('apple_news_id')
                ->clear('apple_news_revision');
        }

        $command = CreateNodeV1::create()->set('node', $notification);
        $pbjx->copyContext($event, $command);

        $operation = $notification->get('apple_news_operation');
        $nodeRef = $article->generateNodeRef();

        if ('create' === $operation) {
            $timestamp = strtotime('+5 seconds');
            $jobId = "{$nodeRef}.create-apple-news";
        } else {
            $timestamp = strtotime('+180 seconds');
            $jobId = "{$nodeRef}.sync-apple-news";
        }

        try {
            $pbjx->sendAt($command, $timestamp, $jobId);
        } catch (\Throwable $e) {
            if ($e->getCode() !== Code::ALREADY_EXISTS) {
                throw $e;
            }
        }
    }

    protected function shouldNotifyAppleNews(Message $event, Message $article): bool
    {
        // override to implement your own check to block apple news updates
        // based on the event or article
        return true;
    }
}
