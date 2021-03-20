<?php
declare(strict_types=1);

namespace Triniti\Ncr\Search\Elastica;

use Elastica\Mapping;
use Gdbots\Ncr\Search\Elastica\MappingBuilder as BaseMappingBuilder;
use Gdbots\Pbj\Field;
use Gdbots\Pbj\Schema;

class MappingBuilder extends BaseMappingBuilder
{
    public function build(): Mapping
    {
        foreach (PromotionMapper::DAYS_OF_WEEK as $dow) {
            $this->properties["d__{$dow}_start_at"] = ['type' => 'integer'];
            $this->properties["d__{$dow}_end_at"] = ['type' => 'integer'];
        }

        return parent::build();
    }

    protected function filterProperties(Schema $schema, Field $field, string $path, array $properties): array
    {
        $properties = parent::filterProperties($schema, $field, $path, $properties);

        switch ($field->getName()) {
            case 'first_name':
            case 'last_name':
                $properties['fields'] = [
                    'raw' => ['type' => 'keyword', 'normalizer' => 'pbj_keyword'],
                ];
                break;

            case 'hashtags':
                $properties['fields'] = [
                    'suggest' => ['type' => 'completion', 'analyzer' => 'pbj_keyword'],
                ];
                break;

            case 'jwplayer_media_id':
            case 'kaltura_entry_id':
                $properties['copy_to'] = self::ALL_FIELD;
                break;

            case 'swipe':
                unset($properties['copy_to']);
                break;

            default:
                break;
        }

        return $properties;
    }

    protected function shouldIgnoreField(Field $field, string $path): bool
    {
        $ignoredNames = [
            'answer_votes'             => true,
            'cta_text'                 => true,
            'hf_sizes'                 => true,
            'hf_styles'                => true,
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
            str_contains($path, 'blocks.size') => true,
            default => false,
        };
    }
}
