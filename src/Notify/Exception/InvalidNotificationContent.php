<?php
declare(strict_types=1);

namespace Triniti\Notify\Exception;

use Gdbots\Schemas\Pbjx\Enum\Code;

final class InvalidNotificationContent extends \InvalidArgumentException implements TrinitiNotifyException
{
    public function __construct(string $message = 'Selected content does not support notifications.')
    {
        parent::__construct($message, Code::INVALID_ARGUMENT);
    }
}
