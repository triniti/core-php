<?php
declare(strict_types=1);

namespace Triniti\News\Enricher;

use Gdbots\Pbj\Message;
use Gdbots\Pbjx\DependencyInjection\PbjxEnricher;
use Gdbots\Pbjx\Event\PbjxEvent;
use Gdbots\Pbjx\EventSubscriber;

final class ArticleEnricher implements EventSubscriber, PbjxEnricher
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            'triniti:news:mixin:article.enrich' => 'enrichWithWordCount',
        ];
    }

    public function enrichWithWordCount(PbjxEvent $pbjxEvent): void
    {
        /** @var Message $node */
        $node = $pbjxEvent->getMessage();
        if ($node->isFrozen()) {
            return;
        }

        $text = [$node->get('title')];

        /** @var Message $block */
        foreach ($node->get('blocks', []) as $block) {
            if ($block::schema()->hasMixin('triniti:canvas:mixin:text-block')) {
                $text[] = $block->get('text', '');
            } elseif ($block::schema()->hasMixin('triniti:canvas:mixin:heading-block')) {
                $text[] = $block->get('text', '');
            } elseif ($block::schema()->hasMixin('triniti:canvas:mixin:quote-block')) {
                $text[] = $block->get('text', '');
            }
        }

        $wordCount = (int)str_word_count(strip_tags(implode(' ', $text)));
        $node->set('word_count', $wordCount);
    }
}
