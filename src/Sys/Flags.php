<?php
declare(strict_types=1);

namespace Triniti\Sys;

use Gdbots\Ncr\Ncr;
use Gdbots\Pbj\Message;
use Gdbots\Pbj\MessageResolver;
use Gdbots\Pbj\WellKnown\NodeRef;

class Flags
{
    protected Ncr $ncr;
    protected NodeRef $flagsetRef;
    protected ?Message $flagset = null;

    public function __construct(Ncr $ncr, string $flagset)
    {
        $this->flagsetRef = NodeRef::fromString($flagset);
        $this->ncr = $ncr;
    }

    public function getAll(): Message
    {
        $this->loadFlagset();
        return $this->flagset;
    }

    public function getBoolean(string $flag, bool $default = false): bool
    {
        $this->loadFlagset();
        return $this->flagset->getFromMap('booleans', $flag, $default);
    }

    public function getFloat(string $flag, float $default = 0.0): float
    {
        $this->loadFlagset();
        return $this->flagset->getFromMap('floats', $flag, $default);
    }

    public function getInt(string $flag, int $default = 0): int
    {
        $this->loadFlagset();
        return $this->flagset->getFromMap('ints', $flag, $default);
    }

    public function getString(string $flag, string $default = ''): string
    {
        $this->loadFlagset();
        return $this->flagset->getFromMap('strings', $flag, $default);
    }

    public function getTrinary(string $flag, int $default = 0): int
    {
        $this->loadFlagset();
        return $this->flagset->getFromMap('trinaries', $flag, $default);
    }

    /**
     * Loads a Flagset from the Ncr and if it fails
     * creates an empty one so this should never
     * throw an exception.
     */
    protected function loadFlagset(): void
    {
        if (null !== $this->flagset) {
            return;
        }

        try {
            $this->flagset = $this->ncr->getNode($this->flagsetRef);
        } catch (\Throwable $e) {
            $this->flagset = MessageResolver::resolveCurie('*:sys:node:flagset:v1')::fromArray([
                '_id' => $this->flagsetRef->getId(),
            ]);
        }

        $this->flagset->freeze();
    }
}
