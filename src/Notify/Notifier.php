<?php
declare(strict_types=1);

namespace Triniti\Notify;

use Gdbots\Pbj\Message;

interface Notifier
{
    public function send(Message $notification, Message $app, ?Message $content = null): Message;
}
