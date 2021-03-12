<?php
declare(strict_types=1);

namespace Triniti\Ncr\Search\Elastica;

use Elastica\Document;
use Gdbots\Pbj\Message;

class PromotionMapper extends NodeMapper
{
    public const DAYS_OF_WEEK = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];

    public function beforeIndex(Document $document, Message $node): void
    {
        parent::beforeIndex($document, $node);

        foreach (self::DAYS_OF_WEEK as $dow) {
            $src = "{$dow}_start_at";
            $target = "d__{$dow}_start_at";
            if ($node->has($src)) {
                $document->set($target, (int)strtotime("{$node->get($src)}UTC", 0));
            }

            $src = "{$dow}_end_at";
            $target = "d__{$dow}_end_at";
            if ($node->has($src)) {
                $document->set($target, (int)strtotime("{$node->get($src)}UTC", 0));
            }
        }
    }
}
