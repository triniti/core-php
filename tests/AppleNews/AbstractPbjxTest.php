<?php
declare(strict_types=1);

namespace Triniti\Tests\AppleNews;

use Gdbots\Ncr\Repository\InMemoryNcr;
use Gdbots\Pbj\Message;
use Gdbots\Pbjx\EventStore\InMemoryEventStore;
use Gdbots\Pbjx\Pbjx;
use Gdbots\Pbjx\RegisteringServiceLocator;
use Gdbots\Pbjx\Scheduler\Scheduler;
use Gdbots\Schemas\Pbjx\Mixin\Command\Command;
use PHPUnit\Framework\TestCase;

abstract class AbstractPbjxTest extends TestCase
{
    protected RegisteringServiceLocator $locator;
    protected Pbjx $pbjx;
    protected InMemoryEventStore $eventStore;
    protected InMemoryNcr $ncr;
    protected Scheduler $scheduler;

    protected function setup(): void
    {
        $this->locator = new RegisteringServiceLocator();
        $this->pbjx = $this->locator->getPbjx();
        $this->eventStore = new InMemoryEventStore($this->pbjx);
        $this->locator->setEventStore($this->eventStore);
        $this->ncr = new InMemoryNcr();

        $this->scheduler = new class implements Scheduler {
            public array $lastSendAt;
            public array $lastCancelJobs;

            public function createStorage(array $context = []): void
            {
            }

            public function describeStorage(array $context = []): string
            {
                return '';
            }

            public function sendAt(Message $command, int $timestamp, ?string $jobId = null, array $context = []): string
            {
                $this->lastSendAt = [
                    'command'   => $command,
                    'timestamp' => $timestamp,
                    'job_id'    => $jobId,
                ];
                return $jobId ?: 'jobid';
            }

            public function cancelJobs(array $jobIds, array $context = []): void
            {
                $this->lastCancelJobs = $jobIds;
            }
        };

        $this->locator->setScheduler($this->scheduler);
    }
}
