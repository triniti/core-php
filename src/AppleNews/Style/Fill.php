<?php
declare(strict_types=1);

namespace Triniti\AppleNews\Style;

use Assert\Assertion;
use Triniti\AppleNews\AppleNewsObject;

abstract class Fill extends AppleNewsObject
{
    /** @var string */
    protected $attachment;

    /**
     * @var array
     */
    private $validAttachments = [
        'fixed',
        'scroll',
    ];

    /**
     * @return string
     */
    public function getAttachment(): ?string
    {
        return $this->attachment;
    }

    /**
     * @param string $attachment
     *
     * @return static
     */
    public function setAttachment(string $attachment = 'scroll'): self
    {
        Assertion::inArray($attachment, $this->validAttachments);
        $this->attachment = $attachment;
        return $this;
    }
}
