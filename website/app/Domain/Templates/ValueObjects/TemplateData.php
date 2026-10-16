<?php

declare(strict_types=1);

namespace App\Domain\Templates\ValueObjects;

use InvalidArgumentException;
use JsonSerializable;

/**
 * Complete template data (published or draft): sections in order, plus the design tokens.
 *
 * @property-read list<TemplateSectionConfig> $sections
 * @property-read TemplateDesignTokens $designTokens
 */
final readonly class TemplateData implements JsonSerializable
{
    /**
     * @param  list<TemplateSectionConfig>  $sections
     */
    public function __construct(
        public array $sections,
        public TemplateDesignTokens $designTokens,
    ) {
        if (count($sections) === 0) {
            throw new InvalidArgumentException('A template must have at least one section.');
        }

        // Check unique keys.
        $keys = array_map(fn (TemplateSectionConfig $s) => $s->key, $sections);

        if (count($keys) !== count(array_unique($keys))) {
            throw new InvalidArgumentException('Section keys must be unique within a template.');
        }
    }

    /**
     * @return array{sections: list<array{key: string, section: string, props: array<string, mixed>}>, designTokens: array<string, string>}
     */
    public function jsonSerialize(): array
    {
        return [
            'sections' => array_map(fn (TemplateSectionConfig $s) => $s->jsonSerialize(), $this->sections),
            'designTokens' => $this->designTokens->jsonSerialize(),
        ];
    }

    /**
     * @param  array{sections: list<array{key: string, section: string, props?: array<string, mixed>}>, designTokens: array<string, string>}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            sections: array_map(
                fn (array $s) => TemplateSectionConfig::fromArray($s),
                $data['sections'],
            ),
            designTokens: TemplateDesignTokens::fromArray($data['designTokens']),
        );
    }
}
