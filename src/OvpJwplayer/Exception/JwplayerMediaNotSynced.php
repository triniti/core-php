<?php
declare(strict_types=1);

namespace Triniti\OvpJwplayer\Exception;

use Gdbots\Schemas\Pbjx\Enum\Code;
use Triniti\Ovp\Exception\TrinitiOvpException;

final class JwplayerMediaNotSynced extends \RuntimeException implements TrinitiOvpException
{
    public function __construct(string $message = 'The Jwplayer Media was not synced.')
    {
        parent::__construct($message, Code::INTERNAL);
    }
}
