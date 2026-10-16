<?php

declare(strict_types=1);

namespace App\Domain\Templates\ValueObjects;

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
    ) {
        if (trim($key) === '') {
            throw new InvalidArgumentException('Section key cannot be empty.');
        }
    }

    /**
     * @return array{key: string, section: string, props: array<string, mixed>}
     */
    public function jsonSerialize(): array
    {
        return [
            'key' => $this->key,
            'section' => $this->section,
            'props' => $this->props,
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
        );
    }
}
