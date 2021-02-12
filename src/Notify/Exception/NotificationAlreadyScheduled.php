<?php
declare(strict_types=1);

namespace Triniti\Notify\Exception;

use Gdbots\Schemas\Pbjx\Enum\Code;

final class NotificationAlreadyScheduled extends \RuntimeException implements TrinitiNotifyException
{
    public function __construct(string $message = 'Notification already scheduled.')
    {
        parent::__construct($message, Code::ALREADY_EXISTS);
    }
}
