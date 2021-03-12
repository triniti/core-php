<?php
declare(strict_types=1);

namespace Triniti\Pbjx\EventSearch\Elastica;

use Gdbots\Pbjx\EventSearch\Elastica\ElasticaEventSearch as BaseElasticaEventSearch;
use Gdbots\Pbjx\EventSearch\Elastica\QueryFactory as BaseQueryFactory;

class ElasticaEventSearch extends BaseElasticaEventSearch
{
    protected function doGetQueryFactory(): BaseQueryFactory
    {
        return new QueryFactory();
    }
}
