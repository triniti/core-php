<?php
declare(strict_types=1);

namespace Triniti\News;

use Gdbots\Ncr\Ncr;
use Gdbots\Ncr\NcrProjector;
use Gdbots\Ncr\NcrSearch;
use Gdbots\Pbj\Message;
use Gdbots\Pbj\MessageResolver;
use Gdbots\Pbjx\EventSubscriberTrait;
use Gdbots\Pbjx\Pbjx;
use Gdbots\Schemas\Ncr\Enum\NodeStatus;
use Psr\Cache\CacheItemPoolInterface;
use Triniti\Schemas\News\Command\RemoveArticleSlottingV1;

class NcrArticleProjector extends NcrProjector
{
    use EventSubscriberTrait;

    protected CacheItemPoolInterface $cache;

    public function __construct(
        Ncr $ncr,
        NcrSearch $ncrSearch,
        CacheItemPoolInterface $cache,
        bool $indexOnReplay = false
    ) {
        parent::__construct($ncr, $ncrSearch, $indexOnReplay);
        $this->cache = $cache;
    }

    public static function getSubscribedEvents()
    {
        $vendor = MessageResolver::getDefaultVendor();
        return [
            "{$vendor}:news:event:*" => 'onEvent',
        ];
    }

    public function onAppleNewsArticleSynced(Message $event, Pbjx $pbjx): void
    {
        $nodeRef = $event->get('node_ref');

        $node = $this->ncr->getNode($nodeRef, true);

        if ('delete' === $event->get('apple_news_operation')) {
            $node
                ->clear('apple_news_id')
                ->clear('apple_news_revision')
                ->clear('apple_news_share_url')
                ->clear('apple_news_updated_at')
                ->set('apple_news_enabled', false);
        } else {
            /** @var \DateTime $occurredAt */
            $occurredAt = $event->get('occurred_at')->toDateTime();
            $node
                ->set('apple_news_id', $event->get('apple_news_id'))
                ->set('apple_news_revision', $event->get('apple_news_revision'))
                ->set('apple_news_share_url', $event->get('apple_news_share_url'))
                ->set('apple_news_updated_at', $occurredAt->getTimestamp());
        }

        $this->projectNode($node, $event, $pbjx);
    }

    public function onArticlePublished(Message $event, Pbjx $pbjx): void
    {
        $this->onNodeEvent($event, $pbjx);
        if ($event->isReplay()) {
            return;
        }

        $node = $this->ncr->getNode($event->get('node_ref'));
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

    public function onArticleSlottingRemoved(Message $event, Pbjx $pbjx): void
    {
        $node = $this->ncr->getNode($event->get('node_ref'), true);

        $cacheKeys = [];
        foreach ($event->get('slotting_keys', []) as $key) {
            $node->removeFromMap('slotting', $key);
            $cacheKeys[] = "news.slotting.{$key}.php";
        }

        $this->projectNode($node, $event, $pbjx);

        if ($event->isReplay()) {
            return;
        }

        $this->cache->deleteItems($cacheKeys);
    }

    public function onArticleUpdated(Message $event, Pbjx $pbjx): void
    {
        $this->onNodeEvent($event, $pbjx);
        if ($event->isReplay() || !$event->has('old_node')) {
            return;
        }

        $oldNode = $event->get('old_node');
        $newNode = $event->get('new_node');

        if (!NodeStatus::PUBLISHED()->equals($newNode->get('status'))) {
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
