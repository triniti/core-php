<?php
declare(strict_types=1);

namespace Triniti\Taxonomy;

interface HashtagSuggester
{
    /**
     * @param string $prefix
     * @param int    $count
     * @param array  $context
     *
     * @return string[]
     */
    public function autocomplete(string $prefix, int $count = 25, array $context = []): array;
}
