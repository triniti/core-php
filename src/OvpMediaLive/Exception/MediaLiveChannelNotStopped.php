<?php
declare(strict_types=1);

namespace Triniti\OvpMediaLive\Exception;

use Gdbots\Schemas\Pbjx\Enum\Code;
use Triniti\Ovp\Exception\TrinitiOvpException;

final class MediaLiveChannelNotStopped extends \RuntimeException implements TrinitiOvpException
{
    public function __construct(string $message = 'The MediaLive Channel was not stopped.')
    {
        parent::__construct($message, Code::INTERNAL);
    }
}
