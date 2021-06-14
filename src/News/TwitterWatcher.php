<?php
declare(strict_types=1);

namespace Triniti\News;

use Gdbots\Ncr\Event\NodeProjectedEvent;
use Gdbots\Pbj\Message;
use Gdbots\Pbj\MessageResolver;
use Gdbots\Pbj\WellKnown\UuidIdentifier;
use Gdbots\Pbjx\EventSubscriber;
use Gdbots\Pbjx\Pbjx;
use Gdbots\Schemas\Iam\Request\SearchAppsRequestV1;
use Gdbots\Schemas\Ncr\Command\CreateNodeV1;
use Gdbots\Schemas\Ncr\Enum\NodeStatus;
use Gdbots\Schemas\Pbjx\Enum\Code;
use Ramsey\Uuid\Uuid;

final class TwitterWatcher implements EventSubscriber
{
    public static function getSubscribedEvents(): array
    {
        return [
            'triniti:news:mixin:article.published' => 'onArticlePublished',
        ];
    }

    public function onArticlePublished(NodeProjectedEvent $pbjxEvent): void
    {
        $event = $pbjxEvent->getLastEvent();
        $node = $pbjxEvent->getNode();
        $pbjx = $pbjxEvent::getPbjx();
        $this->notifyTwitter($event, $node, $pbjx);
    }

    protected function createTwitterNotification(Message $event, Message $article, Pbjx $pbjx): Message
    {
        $date = $event->get('occurred_at')->toDateTime();
        $id = UuidIdentifier::fromString(
            Uuid::uuid5('twitter-auto-publish',
            $article->generateNodeRef()->toString())->toString()
        );

        return MessageResolver::resolveCurie('*:notify:node:twitter-notification:v1')::create()
            ->set('title', $article->get('title'))
            ->set('send_at', $date)
            ->set('content_ref',  $event->get('node_ref')
            ->set('_id', $id));
    }

    protected function getApp(Message $article, Message $event, Pbjx $pbjx): ?Message
    {
        $request = SearchAppsRequestV1::create();
        $response = $pbjx->copyContext($event, $request)->request($request);

        /** @var Message $node */
        foreach ($response->get('nodes', []) as $node) {
            if ($node::schema()->hasMixin('gdbots:iam:mixin:twitter-app')
                && NodeStatus::PUBLISHED === $node->fget('status')
            ) {
                return $node;
            }
        }

        return null;
    }

    public function notifyTwitter(Message $event, Message $article, Pbjx $pbjx): void
    {
        if ($event->isReplay()) {
            return;
        }

        if (!$this->shouldNotifyTwitter($event, $article)) {
          return;
        }

        $app = $this->getApp($article, $event, $pbjx);
        if (null === $app) {
            return;
        }

        $notification = $this->createTwitterNotification($event, $article, $pbjx)
            ->set('app_ref', $app->generateNodeRef());

        $command = CreateNodeV1::create()->set('node', $notification);
        $pbjx->copyContext($event, $command);
        $nodeRef = $article->generateNodeRef();

        try {
            $pbjx->sendAt($command, strtotime('+180 seconds'), "{$nodeRef}.post-tweet");
        } catch (\Throwable $e) {
            if ($e->getCode() !== Code::ALREADY_EXISTS) {
                throw $e;
            }
        }
    }

    protected function shouldNotifyTwitter(Message $event, Message $article): bool
    {
        // override to implement your own check to block twitter posts
        // based on the event or article
        return true;
    }
  }
