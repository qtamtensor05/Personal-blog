<?php

declare(strict_types=1);

namespace App\Domain\Templates\ValueObjects;

use InvalidArgumentException;
use JsonSerializable;

/**
 * Template design system: the palette and typography tokens a user can customize.
 *
 * @property-read string $primaryColor
 * @property-read string $secondaryColor
 * @property-read string $accentColor
 * @property-read string $textColor
 * @property-read string $backgroundColor
 * @property-read string $headingFont
 * @property-read string $bodyFont
 */
final readonly class TemplateDesignTokens implements JsonSerializable
{
    public function __construct(
        public string $primaryColor,
        public string $secondaryColor,
        public string $accentColor,
        public string $textColor,
        public string $backgroundColor,
        public string $headingFont,
        public string $bodyFont,
    ) {
        foreach ([$primaryColor, $secondaryColor, $accentColor, $textColor, $backgroundColor] as $color) {
            if (! preg_match('/^#[0-9A-Fa-f]{6}$/', $color)) {
                throw new InvalidArgumentException("Invalid hex color: {$color}");
            }
        }

        foreach ([$headingFont, $bodyFont] as $font) {
            if (trim($font) === '') {
                throw new InvalidArgumentException('Font name cannot be empty.');
            }
        }
    }

    /**
     * @return array<string, string>
     */
    public function jsonSerialize(): array
    {
        return [
            'primaryColor' => $this->primaryColor,
            'secondaryColor' => $this->secondaryColor,
            'accentColor' => $this->accentColor,
            'textColor' => $this->textColor,
            'backgroundColor' => $this->backgroundColor,
            'headingFont' => $this->headingFont,
            'bodyFont' => $this->bodyFont,
        ];
    }

    /**
     * @param  array<string, string>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            primaryColor: $data['primaryColor'],
            secondaryColor: $data['secondaryColor'],
            accentColor: $data['accentColor'],
            textColor: $data['textColor'],
            backgroundColor: $data['backgroundColor'],
            headingFont: $data['headingFont'],
            bodyFont: $data['bodyFont'],
        );
    }

    public static function default(): self
    {
        // Matches existing app.css palette.
        return new self(
            primaryColor: '#90B250',
            secondaryColor: '#BD927D',
            accentColor: '#E0BE75',
            textColor: '#616B5E',
            backgroundColor: '#F7F6F1',
            headingFont: 'Instrument Sans',
            bodyFont: 'Instrument Sans',
        );
    }
}
