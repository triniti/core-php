<?php
declare(strict_types=1);

namespace Triniti\Sys\Twig;

use Gdbots\Pbj\Message;
use Gdbots\Schemas\Common\Enum\Trinary;
use Triniti\Sys\Flags;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class FlagsExtension extends AbstractExtension
{
    private Flags $flags;

    public function __construct(Flags $flags)
    {
        $this->flags = $flags;
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('flags_get_all', [$this, 'getAll']),
            new TwigFunction('flags_get_boolean', [$this, 'getBoolean']),
            new TwigFunction('flags_get_float', [$this, 'getFloat']),
            new TwigFunction('flags_get_int', [$this, 'getInt']),
            new TwigFunction('flags_get_string', [$this, 'getString']),
            new TwigFunction('flags_get_trinary', [$this, 'getTrinary']),
        ];
    }

    public function getAll(): Message
    {
        return $this->flags->getAll();
    }

    public function getBoolean(string $flag, bool $default = false): bool
    {
        return $this->flags->getBoolean($flag, $default);
    }

    public function getFloat(string $flag, float $default = 0.0): float
    {
        return $this->flags->getFloat($flag, $default);
    }

    public function getInt(string $flag, int $default = 0): int
    {
        return $this->flags->getInt($flag, $default);
    }

    public function getString(string $flag, string $default = ''): string
    {
        return $this->flags->getString($flag, $default);
    }

    public function getTrinary(string $flag, int $default = Trinary::UNKNOWN): int
    {
        return $this->flags->getTrinary($flag, $default);
    }
}
