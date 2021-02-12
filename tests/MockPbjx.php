<?php
declare(strict_types=1);

namespace Triniti\Tests;

use Gdbots\Pbj\Message;
use Gdbots\Pbj\Util\NumberUtil;
use Gdbots\Pbjx\Event\PbjxEvent;
use Gdbots\Pbjx\EventSearch\EventSearch;
use Gdbots\Pbjx\EventStore\EventStore;
use Gdbots\Pbjx\Pbjx;
use Gdbots\Pbjx\ServiceLocator;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class MockPbjx implements Pbjx
{
    private EventDispatcherInterface $dispatcher;
    private ServiceLocator $locator;
    private int $maxRecursion;
    private array $sent = [];
    private bool $canceled = false;

    public function __construct(ServiceLocator $locator, int $maxRecursion = 10)
    {
        $this->locator = $locator;
        $this->dispatcher = $this->locator->getDispatcher();
        $this->maxRecursion = NumberUtil::bound($maxRecursion, 2, 10);
        PbjxEvent::setPbjx($this);
    }

    public function getSent(): array
    {
        return $this->sent;
    }

    public function getCanceled(): bool
    {
        return $this->canceled;
    }

    public function trigger(Message $message, string $suffix, ?PbjxEvent $event = null, bool $recursive = true): Pbjx
    {
        return $this;
    }

    public function triggerLifecycle(Message $message, bool $recursive = true): Pbjx
    {
        return $this;
    }

    public function copyContext(Message $from, Message $to): Pbjx
    {
        return $this;
    }

    public function send(Message $command): void
    {
    }

    public function sendAt(Message $command, int $timestamp, ?string $jobId = null, array $context = []): string
    {
        $this->sent[] = [
            'command'   => $command,
            'timestamp' => $timestamp,
        ];
        return $jobId ?: 'jobid';
    }

    public function cancelJobs(array $jobIds, array $context = []): void
    {
        $this->canceled = true;
    }

    public function publish(Message $event): void
    {
    }

    public function request(Message $request): Message
    {
    }

    public function getEventStore(): EventStore
    {
        return $this->locator->getEventStore();
    }

    public function getEventSearch(): EventSearch
    {
    }
}
