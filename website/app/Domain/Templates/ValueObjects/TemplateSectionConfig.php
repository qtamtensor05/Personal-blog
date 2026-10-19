<?php

declare(strict_types=1);

namespace App\Domain\Templates\ValueObjects;

use App\Domain\Templates\Enums\TemplateRegion;
use App\Domain\Templates\Enums\TemplateSection;
use App\Domain\Templates\TemplateSlotCatalog;
use InvalidArgumentException;
use JsonSerializable;

/**
 * A section instance in a template's ordered section list.
 *
 * @property-read string $key Unique key within the template.
 * @property-read string $section Section type (value of TemplateSection enum).
 * @property-read array<string, mixed> $props Section-specific settings.
 */
final readonly class TemplateSectionConfig implements JsonSerializable
{
    /**
     * @param  array<string, mixed>  $props
     */
    public function __construct(
        public string $key,
        public string $section,
        public array $props = [],
        public string $region = 'body',
        public ?string $label = null,
        public bool $visible = true,
    ) {
        if (trim($key) === '') {
            throw new InvalidArgumentException('Section key cannot be empty.');
        }

        // Only controlled components; never arbitrary HTML/JS.
        if (TemplateSection::tryFrom($section) === null) {
            throw new InvalidArgumentException("Unknown section type: {$section}");
        }

        if (
            TemplateRegion::tryFrom($region) === null ||
            ! in_array($region, TemplateSlotCatalog::allowedRegions($section), true)
        ) {
            throw new InvalidArgumentException("Section {$section} cannot be placed in {$region}.");
        }
    }

    /**
     * @return array{key: string, section: string, props: array<string, mixed>, region: string, label: ?string, visible: bool}
     */
    public function jsonSerialize(): array
    {
        return [
            'key' => $this->key,
            'section' => $this->section,
            'props' => $this->props,
            'region' => $this->region,
            'label' => $this->label,
            'visible' => $this->visible,
        ];
    }

    /**
     * @param  array{key: string, section: string, props?: array<string, mixed>}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            key: $data['key'],
            section: $data['section'],
            props: $data['props'] ?? [],
            region: $data['region'] ?? TemplateSlotCatalog::defaultRegion($data['section'])->value,
            label: isset($data['label']) ? (string) $data['label'] : null,
            visible: ! array_key_exists('visible', $data) || (bool) $data['visible'],
        );
    }
}
