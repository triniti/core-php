<?php
declare(strict_types=1);

namespace Triniti\AppleNews\Style;

use Assert\Assertion;
use Triniti\AppleNews\AppleNewsObject;

/**
 * @link https://developer.apple.com/documentation/apple_news/list_item_style
 */
class ListItemStyle extends AppleNewsObject
{
    protected string $type = 'bullet';
    protected ?string $character = null;

    /**
     * @var array
     */
    private array $validTypes = [
        'bullet',
        'decimal',
        'lower_alphabetical',
        'upper_alphabetical',
        'lower_roman',
        'upper_roman',
        'character',
        'none',
    ];

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type = 'bullet'): self
    {
        Assertion::inArray($type, $this->validTypes, 'type does not have a valid value.');
        $this->type = $type;
        return $this;
    }

    public function getCharacter(): ?string
    {
        return $this->character;
    }

    public function setCharacter(string $character): self
    {
        Assertion::eq(strlen($character), 1, 'Only a single character is supported.');
        $this->character = $character;
        return $this;
    }

    public function jsonSerialize()
    {
        return $this->getSetProperties();
    }

    public function validate(): void
    {
        Assertion::notNull($this->type, 'Type is required');

        if ($this->type === 'character') {
            Assertion::notNull($this->character, 'A character should be provided when type is "character"');
        }
    }
}
