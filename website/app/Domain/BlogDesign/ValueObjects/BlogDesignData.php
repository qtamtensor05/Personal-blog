<?php

declare(strict_types=1);

namespace App\Domain\BlogDesign\ValueObjects;

use App\Domain\Templates\ValueObjects\TemplateData;
use App\Domain\Templates\ValueObjects\TemplateDesignTokens;
use App\Domain\Templates\ValueObjects\TemplateSectionConfig;
use JsonSerializable;

/**
 * A blog's own design: a snapshot of the template it was created from, plus the owner's token overrides.
 * The snapshot means later edits to the template never overwrite a blog's design.
 */
final readonly class BlogDesignData implements JsonSerializable
{
    /**
     * @param  list<TemplateSectionConfig>  $sections
     */
    public function __construct(
        public int $templateId,
        public int $templateVersion,
        public array $sections,
        public TemplateDesignTokens $designTokens,
    ) {}

    public static function fromTemplate(int $templateId, int $templateVersion, TemplateData $data): self
    {
        return new self($templateId, $templateVersion, $data->sections, $data->designTokens);
    }

    public function withDesignTokens(TemplateDesignTokens $tokens): self
    {
        return new self($this->templateId, $this->templateVersion, $this->sections, $tokens);
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'templateId' => $this->templateId,
            'templateVersion' => $this->templateVersion,
            'sections' => array_map(fn (TemplateSectionConfig $s) => $s->jsonSerialize(), $this->sections),
            'designTokens' => $this->designTokens->jsonSerialize(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            templateId: (int) $data['templateId'],
            templateVersion: (int) $data['templateVersion'],
            sections: array_map(fn (array $s) => TemplateSectionConfig::fromArray($s), $data['sections']),
            designTokens: TemplateDesignTokens::fromArray($data['designTokens']),
        );
    }
}
