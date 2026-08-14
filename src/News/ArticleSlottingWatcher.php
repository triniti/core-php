<?php
declare(strict_types=1);

namespace Triniti\News;

use Gdbots\Ncr\Event\NodeProjectedEvent;
use Gdbots\Pbjx\EventSubscriber;
use Gdbots\Schemas\Ncr\Enum\NodeStatus;
use Triniti\Schemas\News\Command\RemoveArticleSlottingV1;

class ArticleSlottingWatcher implements EventSubscriber
{
    public static function getSubscribedEvents(): array
    {
        return [
            'triniti:news:mixin:article.published'        => 'onArticlePublished',
            'triniti:news:mixin:article.updated'          => 'onArticleUpdated',
        ];
    }

    public function onArticlePublished(NodeProjectedEvent $pbjxEvent): void
    {
        $pbjx = $pbjxEvent::getPbjx();
        $event = $pbjxEvent->getLastEvent();
        $node = $pbjxEvent->getNode();

        if ($event->isReplay()) {
            return;
        }

        if (!$node->has('slotting')) {
            return;
        }

        $command = RemoveArticleSlottingV1::create()
            ->set('except_ref', $event->get('node_ref'));

        foreach ($node->get('slotting') as $key => $value) {
            $command->addToMap('slotting', $key, $value);
        }

        $pbjx->copyContext($event, $command);
        $pbjx->sendAt($command, strtotime('+5 seconds'));
    }

    public function onArticleUpdated(NodeProjectedEvent $pbjxEvent): void
    {
        $pbjx = $pbjxEvent::getPbjx();
        $event = $pbjxEvent->getLastEvent();

        if ($event->isReplay() || !$event->has('old_node')) {
            return;
        }

        /** @var Message $oldNode */
        $oldNode = $event->get('old_node');
        $newNode = $pbjxEvent->getNode();

        if (NodeStatus::PUBLISHED->value !== $newNode->fget('status')) {
            return;
        }

        $oldSlotting = $oldNode->get('slotting', []);
        $newSlotting = $newNode->get('slotting', []);
        ksort($oldSlotting);
        ksort($newSlotting);

        if ($oldSlotting === $newSlotting || empty($newSlotting)) {
            return;
        }

        $command = RemoveArticleSlottingV1::create()
            ->set('except_ref', $event->get('node_ref'));

        foreach ($newNode->get('slotting') as $key => $value) {
            $command->addToMap('slotting', $key, $value);
        }

        $pbjx->copyContext($event, $command);
        $pbjx->sendAt($command, strtotime('+5 seconds'));
    }
}
