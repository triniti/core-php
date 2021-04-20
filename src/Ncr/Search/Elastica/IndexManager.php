<?php
declare(strict_types=1);

namespace Triniti\Ncr\Search\Elastica;

use Gdbots\Ncr\Search\Elastica\IndexManager as BaseIndexManager;

class IndexManager extends BaseIndexManager
{
    protected function getMappingBuilder(): MappingBuilder
    {
        return new MappingBuilder();
    }
}
