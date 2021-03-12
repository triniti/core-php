<?php
declare(strict_types=1);

namespace Triniti\Ncr\Search\Elastica;

use Elastica\Document;
use Gdbots\Pbj\Message;

class PollStatsMapper extends NodeMapper
{
    public function beforeIndex(Document $document, Message $node): void
    {
        parent::beforeIndex($document, $node);

        if ($document->has('answer_votes')) {
            $document->remove('answer_votes');
        }
    }
}
