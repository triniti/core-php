<?php
declare(strict_types=1);

namespace Triniti\Dam\Exception;

use Gdbots\Schemas\Pbjx\Enum\Code;

class InvalidArgumentException extends \InvalidArgumentException implements TrinitiDamException
{
    public function __construct(string $message = 'Invalid argument.', ?\Throwable $previous = null)
    {
        parent::__construct($message, Code::INVALID_ARGUMENT->value, $previous);
    }
}
