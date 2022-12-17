<?php
declare(strict_types=1);

namespace Triniti\Taxonomy;

use Elastica\Index;
use Elastica\Query;
use Elastica\ResultSet;
use Elastica\Search;
use Elastica\Suggest;
use Elastica\Suggest\Completion;
use Gdbots\Ncr\Search\Elastica\ElasticaNcrSearch;
use Gdbots\Pbj\Util\HashtagUtil;

class ElasticaHashtagSuggester extends ElasticaNcrSearch implements HashtagSuggester
{
    public function autocomplete(string $prefix, int $count = 25, array $context = []): array
    {
        $context = $this->enrichContext(__FUNCTION__, $context);
        $client = $this->getClientForRead($context);
        $search = new Search($client);
        $search->addIndex(new Index($client, $this->indexManager->getIndexPrefix($context)));
        $options = [
            Search::OPTION_TIMEOUT                   => $this->timeout,
            Search::OPTION_FROM                      => 0,
            Search::OPTION_SIZE                      => $count,
            Search::OPTION_SEARCH_IGNORE_UNAVAILABLE => true,
        ];

        $completion = new Completion('hashtags', 'hashtags.suggest');
        $completion->setPrefix($prefix);

        $suggest = new Suggest();
        $suggest->addSuggestion($completion);

        $query = new Query();
        $query->setParam('suggest', $suggest)->setParam('_source', false);

        try {
            $results = $search->setOptionsAndQuery($options, $query)->search();
        } catch (\Throwable $e) {
            $this->logger->error(
                sprintf('%s::completion query failed for [{prefix}].', __CLASS__),
                [
                    'exception' => $e,
                    'prefix'    => $prefix,
                    'context'   => $context,
                ]
            );

            return [];
        }

        return $this->getHashtagsFromResults($results);
    }

    protected function getHashtagsFromResults(ResultSet $results): array
    {
        $suggests = $results->getSuggests();
        if (!isset($suggests['hashtags'], $suggests['hashtags'][0])) {
            return [];
        }

        $options = $suggests['hashtags'][0]['options'] ?? [];
        $hashtags = [];

        foreach ($options as $option) {
            $hashtag = trim($option['text']);
            if (HashtagUtil::isValid($hashtag)) {
                $hashtags[strtolower($hashtag)] = $hashtag;
            }
        }

        return array_values($hashtags);
    }
}
