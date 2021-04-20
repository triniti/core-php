<?php
declare(strict_types=1);

namespace Triniti\Ncr\Repository\DynamoDb;

use Gdbots\Ncr\Repository\DynamoDb\NodeTable;

class AssetTable extends NodeTable
{
    protected function getIndexes(): array
    {
        return [];
    }
}
