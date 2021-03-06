<?php
declare(strict_types=1);

namespace Triniti\Notify;

use Gdbots\Pbj\SchemaCurie;

interface NotifierLocator
{
    public function getNotifier(SchemaCurie $curie): Notifier;
}
