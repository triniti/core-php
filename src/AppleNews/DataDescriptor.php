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
    /** @var string */
    protected $dataType;

    /** @var DataFormat */
    protected $format;

    /** @var string */
    protected $identifier;

    /** @var string */
    protected $key;

    /** @var FormattedText|string */
    protected $label;

    /** @var string[] */
    private $validDataTypes = [
        'string',
        'text',
        'image',
        'number',
        'integer',
        'float',
    ];

    /**
     * @return string
     */
    public function getDataType(): ?string
    {
        return $this->dataType;
    }

    /**
     * @param string $dataType
     *
     * @return static
     */
    public function setDataType(string $dataType): self
    {
        Assertion::inArray($dataType, $this->validDataTypes);
        $this->dataType = $dataType;
        return $this;
    }

    /**
     * @return DataFormat
     */
    public function getFormat(): ?DataFormat
    {
        return $this->format;
    }

    /**
     * @param DataFormat $format
     *
     * @return static
     */
    public function setFormat(?DataFormat $format = null): self
    {
        if ($format) {
            $format->validate();
        }

        $this->format = $format;
        return $this;
    }

    /**
     * @return string
     */
    public function getIdentifier(): ?string
    {
        return $this->identifier;
    }

    /**
     * @param string $identifier
     *
     * @return static
     */
    public function setIdentifier(?string $identifier = null): self
    {
        $this->identifier = $identifier;
        return $this;
    }

    /**
     * @return string
     */
    public function getKey(): ?string
    {
        return $this->key;
    }

    /**
     * @param string $key
     *
     * @return static
     */
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

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        $properties = $this->getSetProperties();
        return $properties;
    }

    /**
     * {@inheritdoc}
     */
    public function validate(): void
    {
        Assertion::notNull($this->dataType);
        Assertion::notNull($this->key);
        Assertion::notNull($this->label);
    }
}
