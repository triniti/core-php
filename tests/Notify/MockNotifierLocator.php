<?php
declare(strict_types=1);

namespace Triniti\Tests\Notify;

use Gdbots\Pbj\SchemaCurie;
use Triniti\Notify\Notifier;
use Triniti\Notify\NotifierLocator;
use Triniti\Tests\Notify\Notifier\MockNotifier;

class MockNotifierLocator implements NotifierLocator
{
    private bool $shouldBeOk;
    private bool $shouldHaveCode;

    public function __construct(bool $shouldBeOk = true, bool $shouldHaveCode = false)
    {
        $this->shouldBeOk = $shouldBeOk;
        $this->shouldHaveCode = $shouldHaveCode;
    }

    public function getNotifier(SchemaCurie $curie): Notifier
    {
        return new MockNotifier($this->shouldBeOk, $this->shouldHaveCode);
    }
}
