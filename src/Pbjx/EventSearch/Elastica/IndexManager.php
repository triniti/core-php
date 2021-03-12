<?php
declare(strict_types=1);

namespace Triniti\Pbjx\EventSearch\Elastica;

use Gdbots\Pbjx\EventSearch\Elastica\IndexManager as BaseIndexManager;

class IndexManager extends BaseIndexManager
{
    protected function getMappingBuilder(): MappingBuilder
    {
        return new MappingBuilder();
    }
}
