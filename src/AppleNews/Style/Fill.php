<?php
declare(strict_types=1);

namespace Triniti\AppleNews\Style;

use Assert\Assertion;
use Triniti\AppleNews\AppleNewsObject;

abstract class Fill extends AppleNewsObject
{
    protected ?string $attachment = null;

    private array $validAttachments = [
        'fixed',
        'scroll',
    ];

    public function getAttachment(): ?string
    {
        return $this->attachment;
    }

    public function setAttachment(string $attachment = 'scroll'): self
    {
        Assertion::inArray($attachment, $this->validAttachments);
        $this->attachment = $attachment;
        return $this;
    }
}
