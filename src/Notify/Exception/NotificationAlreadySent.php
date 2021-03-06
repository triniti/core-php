<?php
declare(strict_types=1);

namespace Triniti\Notify\Exception;

use Gdbots\Schemas\Pbjx\Enum\Code;

final class NotificationAlreadySent extends \RuntimeException implements TrinitiNotifyException
{
    public function __construct(string $message = 'Notification already sent.')
    {
        parent::__construct($message, Code::FAILED_PRECONDITION);
    }
}
