<?php
declare(strict_types=1);

namespace Triniti\Ncr;

use Gdbots\Ncr\NodeIdempotencyValidator as BaseNodeIdempotencyValidator;
use Gdbots\Pbj\Message;

class NodeIdempotencyValidator extends BaseNodeIdempotencyValidator
{
    public function getIdempotencyKeys(Message $node): array
    {
        $keys = parent::getIdempotencyKeys($node);
        if (!$node->has('apple_news_operation')) {
            return $keys;
        }

        /*
         * we want to eliminate duplicate articles and operations to
         * apple news and this check needs to be based on the combo
         * of app, content and operation to give us a window of time
         * that's more reliable against data that isn't changing.
         *
         * this is because the "title" either has a timestamp in it
         * (already gets past this check) or is based on title of
         * node which can be changing on each update of the article
         */
        $operation = $node->get('apple_news_operation');
        $appRef = (string)$node->get('app_ref');
        $contentRef = (string)$node->get('content_ref');
        $keys[] = $this->getCacheKey($node::schema()->getQName(), "{$operation}-{$appRef}-{$contentRef}");

        return $keys;
    }
}
