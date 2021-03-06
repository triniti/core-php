<?php
declare(strict_types=1);

namespace Triniti\Curator\Exception;

use Gdbots\Schemas\Pbjx\Enum\Code;

final class TargetNotPublished extends \RuntimeException implements TrinitiCuratorException
{
    public function __construct(string $message = 'Target not published.')
    {
        parent::__construct($message, Code::FAILED_PRECONDITION);
    }
}
