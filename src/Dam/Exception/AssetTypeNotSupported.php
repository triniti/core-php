<?php
declare(strict_types=1);

namespace Triniti\Dam\Exception;

final class AssetTypeNotSupported extends InvalidArgumentException implements TrinitiDamException
{
    public function __construct(string $message = 'The asset type is not supported.')
    {
        parent::__construct($message);
    }
}
