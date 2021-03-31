<?php
declare(strict_types=1);

namespace Triniti\Ncr\Search\Elastica;

use Elastica\Document;
use Gdbots\Pbj\Message;

class EmailAppMapper extends NodeMapper
{
    // fixme: use different format for keys (they are emails right now) or get jiggy with a new beforeUnmarshal
    public function beforeIndex(Document $document, Message $node): void
    {
        parent::beforeIndex($document, $node);

        if ($document->has('sendgrid_senders')) {
            $document->remove('sendgrid_senders');
        }
    }
}
