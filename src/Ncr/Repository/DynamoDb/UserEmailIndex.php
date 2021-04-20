<?php
declare(strict_types=1);

namespace Triniti\Ncr\Repository\DynamoDb;

use Gdbots\Ncr\Repository\DynamoDb\AbstractIndex;

final class UserEmailIndex extends AbstractIndex
{
    public function getAlias(): string
    {
        return 'email';
    }

    public function getHashKeyName(): string
    {
        return 'email';
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
            'is_blocked' => ['AttributeName' => 'is_blocked', 'AttributeType' => 'BOOL'],
            'labels'     => ['AttributeName' => 'labels', 'AttributeType' => 'SS'],
            'networks'   => ['AttributeName' => 'networks', 'AttributeType' => 'M'],
            'status'     => ['AttributeName' => 'status', 'AttributeType' => 'S'],
            'tags'       => ['AttributeName' => 'tags', 'AttributeType' => 'M'],
        ];
    }

    public function getProjection(): array
    {
        return [
            'ProjectionType'   => 'INCLUDE',
            'NonKeyAttributes' => [
                'etag',
                'hashtags',
                'is_blocked',
                'labels',
                'networks',
                'status',
                'tags',
            ],
        ];
    }
}
