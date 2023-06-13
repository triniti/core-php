<?php
declare(strict_types=1);

namespace Triniti\Notify\Exception;

use Gdbots\Schemas\Pbjx\Enum\Code;

final class UnableToFetchAccessToken extends \RuntimeException implements TrinitiNotifyException
{
    public function __construct(string $message = 'Failed to retrieve access token.')
    {
        parent::__construct($message, Code::FAILED_PRECONDITION->value);
    }
}
