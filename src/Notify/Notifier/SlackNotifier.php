<?php
declare(strict_types=1);

namespace Triniti\Notify\Notifier;

use Gdbots\Pbj\Message;
use Gdbots\Schemas\Pbjx\Enum\Code;
use Triniti\Notify\Notifier;
use Triniti\Schemas\Notify\NotifierResultV1;

class SlackNotifier implements Notifier
{
    public function send(Message $notification, Message $app, ?Message $content = null): Message
    {
        return NotifierResultV1::create()
            ->set('ok', false)
            ->set('code', Code::UNIMPLEMENTED)
            ->set('error_name', 'SlackNotifierNotImplemented');
    }
}
