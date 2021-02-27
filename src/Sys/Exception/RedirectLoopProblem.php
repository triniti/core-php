<?php
declare(strict_types=1);

namespace Triniti\Sys\Exception;

use Gdbots\Schemas\Pbjx\Enum\Code;

final class RedirectLoopProblem extends \InvalidArgumentException implements TrinitiSysException
{
    public function __construct(string $message = 'Request URI and destination cannot be the same.')
    {
        parent::__construct($message, Code::INVALID_ARGUMENT);
    }
}
