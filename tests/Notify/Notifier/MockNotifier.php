<?php
declare(strict_types=1);

namespace Triniti\Tests\Notify\Notifier;

use Gdbots\Pbj\Message;
use Gdbots\Schemas\Pbjx\Enum\Code;
use Triniti\Notify\Notifier;
use Triniti\Schemas\Notify\NotifierResultV1;

class MockNotifier implements Notifier
{
    private bool $shouldBeOk;
    private bool $shouldHaveCode;

    public function __construct(bool $shouldBeOk, bool $shouldHaveCode)
    {
        $this->shouldBeOk = $shouldBeOk;
        $this->shouldHaveCode = $shouldHaveCode;
    }

    public function send(Message $notification, Message $app, ?Message $content = null): Message
    {
        $result = NotifierResultV1::create();
        if (!$this->shouldBeOk) {
            $result->set('ok', false);
        }
        if ($this->shouldHaveCode) {
            $result->set('code', Code::UNAVAILABLE);
        }
        return $result;
    }
}
