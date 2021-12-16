<?php
declare(strict_types=1);

namespace Triniti\Notify\Exception;

use Gdbots\Schemas\Pbjx\Enum\Code;

final class RequiredFieldNotSet extends \InvalidArgumentException implements TrinitiNotifyException
{
    public function __construct(string $message = 'Required field is missing')
    {
        parent::__construct($message, Code::INVALID_ARGUMENT->value);
    }
}
