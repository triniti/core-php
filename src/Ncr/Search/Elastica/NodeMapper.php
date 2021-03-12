<?php
declare(strict_types=1);

namespace Triniti\Ncr\Search\Elastica;

use Elastica\Document;
use Gdbots\Ncr\Search\Elastica\NodeMapper as BaseNodeMapper;
use Gdbots\Pbj\Message;

class NodeMapper extends BaseNodeMapper
{
    public function beforeIndex(Document $document, Message $node): void
    {
        parent::beforeIndex($document, $node);

        // fixme: verify if this is still true
        // ES doesn't support null when completion type is used
        // so remove the field entirely if not populated
        if (!$node->has('hashtags') && $document->has('hashtags')) {
            $document->remove('hashtags');
        }
    }
}
