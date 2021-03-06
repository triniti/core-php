<?php
declare(strict_types=1);

namespace Triniti\Tests\Curator;

use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

final class MockCache implements CacheItemPoolInterface {
    public function clear()
    {
        // TODO: Implement clear() method.
    }
    public function commit()
    {
        // TODO: Implement commit() method.
    }
    public function deleteItem($key)
    {
        // TODO: Implement deleteItem() method.
    }
    public function deleteItems(array $keys)
    {
        // TODO: Implement deleteItems() method.
    }
    public function hasItem($key)
    {
        // TODO: Implement hasItem() method.
    }
    public function getItem($key)
    {
        // TODO: Implement getItem() method.
    }
    public function getItems(array $keys = array())
    {
        // TODO: Implement getItems() method.
    }
    public function save(CacheItemInterface $item)
    {
        // TODO: Implement save() method.
    }
    public function saveDeferred(CacheItemInterface $item)
    {
        // TODO: Implement saveDeferred() method.
    }
}
