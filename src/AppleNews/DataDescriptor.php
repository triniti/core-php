<?php
declare(strict_types=1);

namespace Triniti\AppleNews;

use Assert\Assertion;
use Triniti\AppleNews\Style\FormattedText;

/**
 * @link https://developer.apple.com/documentation/apple_news/data_descriptor
 */
class DataDescriptor extends AppleNewsObject
{
    protected ?string $dataType = null;
    protected ?string $identifier = null;
    protected ?string $key = null;
    protected ?DataFormat $format = null;

    /** @var string|FormattedText */
    protected $label = null;

    /** @var string[] */
    private array $validDataTypes = [
        'string',
        'text',
        'image',
        'number',
        'integer',
        'float',
    ];

    public function getDataType(): ?string
    {
        return $this->dataType;
    }

    public function setDataType(string $dataType): self
    {
        Assertion::inArray($dataType, $this->validDataTypes);
        $this->dataType = $dataType;
        return $this;
    }

    public function getFormat(): ?DataFormat
    {
        return $this->format;
    }

    public function setFormat(?DataFormat $format = null): self
    {
        if ($format) {
            $format->validate();
        }

        $this->format = $format;
        return $this;
    }

    public function getIdentifier(): ?string
    {
        return $this->identifier;
    }

    public function setIdentifier(?string $identifier = null): self
    {
        $this->identifier = $identifier;
        return $this;
    }

    public function getKey(): ?string
    {
        return $this->key;
    }

    public function setKey(string $key): self
    {
        $this->key = $key;
        return $this;
    }

    /**
     * @return string|FormattedText
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @param string|FormattedText $label
     *
     * @return static
     */
    public function setLabel($label = null): self
    {
        if (!is_string($label) && (null !== $label) && !($label instanceof FormattedText)) {
            Assertion::true(
                false,
                'label must be a string or instance of FormattedText'
            );
        }

        if ($label instanceof FormattedText) {
            $label->validate();
        }

        $this->label = $label;
        return $this;
    }

    public function jsonSerialize()
    {
        return $this->getSetProperties();
    }

    public function validate(): void
    {
        Assertion::notNull($this->dataType);
        Assertion::notNull($this->key);
        Assertion::notNull($this->label);
    }
}
