<?php
declare(strict_types=1);

namespace Triniti\Pbjx\EventSearch\Elastica;

use Gdbots\Pbj\Field;
use Gdbots\Pbjx\EventSearch\Elastica\MappingBuilder as BaseMappingBuilder;

class MappingBuilder extends BaseMappingBuilder
{
    protected function shouldIgnoreField(Field $field, string $path): bool
    {
        $ignoredNames = [
            'answer_votes'             => true,
            'cta_text'                 => true,
            'ctx_ua'                   => true,
            'launch_text'              => true,
            'prefetched_nodes'         => true,
            'related_articles_heading' => true,
            'related_videos_heading'   => true,
        ];

        if (isset($ignoredNames[$field->getName()])) {
            return true;
        }

        return match (true) {
            str_contains($path, 'blocks.data'),
            str_contains($path, 'blocks.size'),
                $path === 'event.event',
            str_contains($path, 'event.node'),
            str_contains($path, 'event.new_node'),
            str_contains($path, 'event.old_node'),
            str_contains($path, 'event.stats') => true,
            default => false,
        };
    }
}
