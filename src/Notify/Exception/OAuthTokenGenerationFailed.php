<?php
declare(strict_types=1);

namespace Triniti\Notify\Exception;

use Gdbots\Schemas\Pbjx\Enum\Code;

final class OAuthTokenGenerationFailed extends \RuntimeException implements TrinitiNotifyException
{
    public function __construct(string $message = 'Oauth token generation failed.')
    {
        parent::__construct($message, Code::FAILED_PRECONDITION->value);
    }
}
