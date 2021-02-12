<?php
declare(strict_types=1);

namespace Triniti\Tests;

use Gdbots\Ncr\Repository\InMemoryNcr;
use Gdbots\Pbjx\EventStore\InMemoryEventStore;
use Gdbots\Pbjx\Pbjx;
use Gdbots\Pbjx\RegisteringServiceLocator;
use PHPUnit\Framework\TestCase;

abstract class AbstractPbjxTest extends TestCase
{
    protected RegisteringServiceLocator $locator;
    protected Pbjx $pbjx;
    protected InMemoryEventStore $eventStore;
//    protected InMemoryNcr $ncr;

    protected function setup(): void
    {
        $this->locator = new RegisteringServiceLocator();
        $this->pbjx = $this->locator->getPbjx();
        $this->eventStore = new InMemoryEventStore($this->pbjx);
        $this->locator->setEventStore($this->eventStore);
//        $this->ncr = new InMemoryNcr();
    }
}
