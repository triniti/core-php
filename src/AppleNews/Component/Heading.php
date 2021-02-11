<?php
declare(strict_types=1);

namespace Triniti\AppleNews\Component;

use Assert\Assertion;

/**
 * @link https://developer.apple.com/documentation/apple_news/heading
 */
class Heading extends Text
{
    protected ?string $role = null;

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function setRole(string $role): self
    {
        Assertion::inArray(
            $role,
            ['heading', 'heading1', 'heading2', 'heading3', 'heading4', 'heading5', 'heading6'],
            'Role must be one of the following values: heading, heading1, heading2, heading3, heading4, heading5, heading6.'
        );

        $this->role = $role;
        return $this;
    }

    public function validate(): void
    {
        Assertion::notNull($this->role);
        parent::validate();
    }

    public function jsonSerialize()
    {
        $properties = $this->getSetProperties();
        $properties['role'] = $this->role;
        return $properties;
    }
}
