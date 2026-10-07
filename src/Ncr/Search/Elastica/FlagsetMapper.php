<?php
declare(strict_types=1);

namespace Triniti\Ncr\Search\Elastica;

use Elastica\Document;
use Gdbots\Pbj\Message;

class FlagsetMapper extends NodeMapper
{
    public function beforeIndex(Document $document, Message $node): void
    {
        parent::beforeIndex($document, $node);

        foreach (['booleans', 'floats', 'ints', 'strings', 'trinaries'] as $fieldName) {
            if ($document->has($fieldName)) {
                $document->remove($fieldName);
            }
        }
    }
}
