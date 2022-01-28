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
use Triniti\Ncr\Search\Elastica\MappingBuilder;

class TwitterWatcher implements EventSubscriber
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

    protected function createTwitterNotification(Message $event, Message $article, Message $app, Pbjx $pbjx): Message
    {
        $date = $event->get('occurred_at')->toDateTime()->add(new \DateInterval('PT180S'));
        $appRef = $app->generateNodeRef();
        $contentRef = $article->generateNodeRef();
        $id = UuidIdentifier::fromString(
            Uuid::uuid5(
                Uuid::uuid5(Uuid::NIL, 'twitter-auto-post'),
                $contentRef->toString() . $appRef->getId()
            )->toString()
        );

        return MessageResolver::resolveCurie('*:notify:node:twitter-notification:v1')::create()
            ->set('_id', $id)
            ->set('title', $article->get('title') . ' | ' . $app->get('title'))
            ->set('send_at', $date)
            ->set('app_ref', $appRef)
            ->set('content_ref', $contentRef);
    }

    protected function getApps(Message $article, Message $event, Pbjx $pbjx): array
    {
        $typeField = MappingBuilder::TYPE_FIELD;
        $request = SearchAppsRequestV1::create()
            ->set('status', NodeStatus::PUBLISHED())
            ->set('q', "+{$typeField}:twitter-app");

        try {
            $response = $pbjx->copyContext($event, $request)->request($request);
            return $response->get('nodes', []);
        } catch (\Throwable $e) {
            return [];
        }
    }

    protected function notifyTwitter(Message $event, Message $article, Pbjx $pbjx): void
    {
        if ($event->isReplay()) {
            return;
        }

        if (!$article->get('twitter_publish_enabled')) {
            return;
        }

        foreach ($this->getApps($article, $event, $pbjx) as $app) {
            if (!$this->shouldNotifyTwitter($event, $article, $app)) {
                continue;
            }

            try {
                $notification = $this->createTwitterNotification($event, $article, $app, $pbjx);
                $command = CreateNodeV1::create()->set('node', $notification);
                $pbjx->copyContext($event, $command);
                $nodeRef = $article->generateNodeRef();
                $pbjx->sendAt($command, strtotime('+3 seconds'), "{$nodeRef}.{$app->fget('_id')}.post-tweet");
            } catch (\Throwable $e) {
                if ($e->getCode() !== Code::ALREADY_EXISTS) {
                    throw $e;
                }
            }
        }
    }

    protected function shouldNotifyTwitter(Message $event, Message $article, Message $app): bool
    {
        // override to implement your own check to block twitter posts
        // based on the event, article or app
        return true;
    }
}
