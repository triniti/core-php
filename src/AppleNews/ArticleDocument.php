<?php
declare(strict_types=1);

namespace Triniti\AppleNews;

use Assert\Assertion;
use Triniti\AppleNews\Component\Component;
use Triniti\AppleNews\Layout\ComponentLayout;
use Triniti\AppleNews\Layout\Layout;
use Triniti\AppleNews\Style\ComponentStyle;
use Triniti\AppleNews\Style\ComponentTextStyle;
use Triniti\AppleNews\Style\DocumentStyle;
use Triniti\AppleNews\Style\InlineTextStyle;
use Triniti\AppleNews\Style\TextStyle;

/**
 * @link https://developer.apple.com/documentation/apple_news/article_document
 */
class ArticleDocument extends AppleNewsObject
{
    protected string $identifier;
    protected string $title;
    protected string $language = 'en';
    protected Layout $layout;
    protected ?AdvertisingSettings $advertisingSettings = null;
    protected ?AutoPlacement $autoPlacement = null;
    protected ?string $subtitle = null;
    protected Metadata $metadata;
    protected DocumentStyle $documentStyle;

    /** @var Component[] */
    protected array $components = [];

    /** @var TextStyle[] */
    protected array $textStyles = [];

    /** @var ComponentLayout[] */
    protected array $componentLayouts = [];

    /** @var ComponentStyle[] */
    protected array $componentStyles = [];

    /** @var ComponentTextStyle[] */
    protected array $componentTextStyles = [];

    public function getIdentifier(): ?string
    {
        return $this->identifier;
    }

    public function setIdentifier(string $identifier): self
    {
        $this->identifier = $identifier;
        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getLanguage(): string
    {
        return $this->language;
    }

    public function setLanguage(string $language): self
    {
        $this->language = $language;
        return $this;
    }

    public function getLayout(): ?Layout
    {
        return $this->layout;
    }

    public function setLayout(Layout $layout): self
    {
        $layout->validate();
        $this->layout = $layout;
        return $this;
    }

    /**
     * @return Component[]
     */
    public function getComponents(): array
    {
        return $this->components;
    }

    public function addComponent(Component $component): self
    {
        $component->validate();
        $this->components[] = $component;
        return $this;
    }

    public function getAutoPlacement(): ?AutoPlacement
    {
        return $this->autoPlacement;
    }

    public function setAutoPlacement(?AutoPlacement $autoPlacement = null): self
    {
        $this->autoPlacement = $autoPlacement;
        return $this;
    }

    /**
     * @return AdvertisingSettings
     */
    public function getAdvertisingSettings(): ?AdvertisingSettings
    {
        return $this->advertisingSettings;
    }

    /**
     * @param AdvertisingSettings $advertisingSettings
     *
     * @return static
     */
    public function setAdvertisingSettings(?AdvertisingSettings $advertisingSettings = null): self
    {
        $this->advertisingSettings = $advertisingSettings;
        return $this;
    }

    public function getSubtitle(): ?string
    {
        return $this->subtitle;
    }

    /**
     * @param string $subtitle
     *
     * @return static
     */
    public function setSubtitle(?string $subtitle = null): self
    {
        $this->subtitle = $subtitle;
        return $this;
    }

    /**
     * @return Metadata
     */
    public function getMetadata(): ?Metadata
    {
        return $this->metadata;
    }

    /**
     * @param Metadata $metadata
     *
     * @return static
     */
    public function setMetadata(Metadata $metadata): self
    {
        $this->metadata = $metadata;
        return $this;
    }

    /**
     * @return DocumentStyle
     */
    public function getDocumentStyle(): ?DocumentStyle
    {
        return $this->documentStyle;
    }

    /**
     * @param DocumentStyle $documentStyle
     *
     * @return static
     */
    public function setDocumentStyle(DocumentStyle $documentStyle): self
    {
        $this->documentStyle = $documentStyle;
        return $this;
    }

    /**
     * @return TextStyle[]
     */
    public function getTextStyles(): array
    {
        return $this->textStyles;
    }

    /**
     * @param string    $name
     * @param TextStyle $textStyle
     *
     * @return static
     */
    public function setTextStyle(string $name, TextStyle $textStyle): self
    {
        $textStyle->validate();
        $this->textStyles[$name] = $textStyle;
        return $this;
    }

    /**
     * @return ComponentLayout[]
     */
    public function getComponentLayouts(): array
    {
        return $this->componentLayouts;
    }

    /**
     * @param string          $name
     * @param ComponentLayout $layout
     *
     * @return static
     */
    public function setComponentLayout(string $name, ComponentLayout $layout): self
    {
        $layout->validate();
        $this->componentLayouts[$name] = $layout;
        return $this;
    }

    /**
     * @return ComponentStyle[]
     */
    public function getComponentStyles(): array
    {
        return $this->componentStyles;
    }

    /**
     * @param string         $name
     * @param ComponentStyle $componentStyle
     *
     * @return static
     */
    public function setComponentStyle(string $name, ComponentStyle $componentStyle): self
    {
        $componentStyle->validate();
        $this->componentStyles[$name] = $componentStyle;
        return $this;
    }

    /**
     * @return ComponentTextStyle[]
     */
    public function getComponentTextStyles(): array
    {
        return $this->componentTextStyles;
    }

    /**
     * @param string             $name
     * @param ComponentTextStyle $componentTextStyle
     *
     * @return static
     */
    public function setComponentTextStyle(string $name, ComponentTextStyle $componentTextStyle): self
    {
        $componentTextStyle->validate();
        $this->componentTextStyles[$name] = $componentTextStyle;
        return $this;
    }

    /**
     * @throws \Throwable
     */
    public function validate(): void
    {
        Assertion::notNull($this->identifier);
        Assertion::regex($this->identifier, '/[a-zA-Z0-9_:\/-]/');
        Assertion::lessOrEqualThan(strlen($this->identifier), 64);
        Assertion::notNull($this->title);
        Assertion::notNull($this->language);
        Assertion::notNull($this->layout);
        Assertion::notNull($this->components);
        Assertion::notNull($this->componentTextStyles);

        // Validate that global styles and layouts used in components are defined
        // Hold every unique referenced global setting
        $layouts = [];
        $styles = [];
        $textStyles = [];
        $componentTextStyles = [];

        foreach ($this->getComponents() as $component) {
            $this->getGlobalStylesAndLayouts($component, $layouts, $styles, $textStyles, $componentTextStyles);
        }

        foreach ($layouts as $layout) {
            Assertion::keyIsset(
                $this->componentLayouts,
                $layout,
                sprintf('Global component layout [%s] is not defined.', $layout)
            );
        }

        foreach ($styles as $style) {
            Assertion::keyIsset(
                $this->componentStyles,
                $style,
                sprintf('Global component style [%s] is not defined.', $style)
            );
        }

        foreach ($textStyles as $textStyle) {
            Assertion::keyIsset(
                $this->textStyles,
                $textStyle,
                sprintf('Global text style [%s] is not defined.', $textStyle)
            );
        }

        foreach ($componentTextStyles as $componentTextStyle) {
            Assertion::keyIsset(
                $this->componentTextStyles,
                $componentTextStyle,
                sprintf('Global component text style [%s] is not defined.', $componentTextStyle)
            );
        }
    }

    public function jsonSerialize()
    {
        $properties = $this->getSetProperties();
        $properties['version'] = '1.10';
        return $properties;
    }

    /**
     * What is the purpose of this?
     *
     * @param Component            $component
     * @param Layout[]             $layouts
     * @param ComponentStyle[]     $styles
     * @param TextStyle[]          $textStyles
     * @param ComponentTextStyle[] $componentTextStyles
     */
    protected function getGlobalStylesAndLayouts(
        Component $component,
        array &$layouts,
        array &$styles,
        array &$textStyles,
        array &$componentTextStyles
    ): void {
        if (method_exists($component, 'getComponents') && count($component->getComponents()) > 0) {
            // find nested component styles
            foreach ($component->getComponents() as $component) {
                $this->getGlobalStylesAndLayouts(
                    $component,
                    $layouts,
                    $styles,
                    $textStyles,
                    $componentTextStyles
                );
            }
        }

        $layout = $component->getLayout();
        if (isset($layout) && is_string($layout)) {
            $layouts[] = $layout;
        }

        $style = $component->getStyle();
        if (isset($style) && is_string($style)) {
            $styles[] = $style;
        }

        if (method_exists($component, 'getTextStyle') && is_string($component->getTextStyle())) {
            $componentTextStyles[] = $component->getTextStyle();
        }

        if (method_exists($component, 'getInlineTextStyles') && is_array($component->getInlineTextStyles())) {
            /** @var InlineTextStyle $inlineTextStyle */
            foreach ($component->getInlineTextStyles() as $inlineTextStyle) {
                $textStyle = $inlineTextStyle->getTextStyle();
                if (isset($textStyle) && is_string($textStyle)) {
                    $textStyles[] = $textStyle;
                }
            }
        }
    }
}
