<?php
declare(strict_types=1);

namespace Triniti\Ncr\Repository\DynamoDb;

use Gdbots\Ncr\Repository\DynamoDb\AbstractIndex;

final class TeaserTargetIndex extends AbstractIndex
{
    public function getAlias(): string
    {
        return 'target';
    }

    public function getHashKeyName(): string
    {
        return 'target_ref';
    }

    public function getRangeKeyName(): ?string
    {
        return 'created_at';
    }

    public function getKeyAttributes(): array
    {
        return [
            ['AttributeName' => $this->getHashKeyName(), 'AttributeType' => 'S'],
            ['AttributeName' => $this->getRangeKeyName(), 'AttributeType' => 'N'],
        ];
    }

    public function getFilterableAttributes(): array
    {
        return [
            'created_at' => ['AttributeName' => $this->getRangeKeyName(), 'AttributeType' => 'N'],
            'etag'       => ['AttributeName' => 'etag', 'AttributeType' => 'S'],
            'hashtags'   => ['AttributeName' => 'hashtags', 'AttributeType' => 'SS'],
            'status'     => ['AttributeName' => 'status', 'AttributeType' => 'S'],
            'tags'       => ['AttributeName' => 'tags', 'AttributeType' => 'M'],
        ];
    }

    public function getProjection(): array
    {
        return [
            'ProjectionType'   => 'INCLUDE',
            'NonKeyAttributes' => ['etag', 'hashtags', 'status', 'tags'],
        ];
    }
}
