<?php
declare(strict_types=1);

namespace Triniti\Ncr\Search\Elastica;

use Gdbots\Ncr\Search\Elastica\ElasticaNcrSearch as BaseElasticaNcrSearch;
use Gdbots\Ncr\Search\Elastica\QueryFactory as BaseQueryFactory;

class ElasticaNcrSearch extends BaseElasticaNcrSearch
{
    protected function doGetQueryFactory(): BaseQueryFactory
    {
        return new QueryFactory();
    }
}
