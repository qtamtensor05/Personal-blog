<?php

declare(strict_types=1);

namespace App\Application\Templates\Contracts;

use App\Application\Templates\DTOs\TemplateRecord;
use App\Domain\Templates\Enums\TemplateStatus;
use App\Domain\Templates\ValueObjects\TemplateData;

interface TemplateRepository
{
    public function find(int $id): ?TemplateRecord;

    public function slugExists(string $slug, ?int $exceptId = null): bool;

    /**
     * Create a template with draft data only (DRAFT state).
     */
    public function create(
        string $name,
        string $slug,
        ?string $description,
        ?int $previewMediaId,
        ?string $demoUrl,
        int $createdBy,
        TemplateData $draftData,
    ): int;

    /**
     * Update the draft. Does not change published data or version.
     */
    public function updateDraft(
        int $id,
        string $name,
        string $slug,
        ?string $description,
        ?int $previewMediaId,
        ?string $demoUrl,
        int $updatedBy,
        TemplateData $draftData,
    ): void;

    /**
     * Promote draft to published: copy draft_data to template_data, increment version, change status.
     */
    public function publish(int $id, int $publishedBy, TemplateStatus $status): void;

    /**
     * Change status without touching data.
     */
    public function setStatus(int $id, TemplateStatus $status): void;

    /**
     * Soft delete: set deleted_at.
     */
    public function softDelete(int $id): void;

    /**
     * All templates (admin list), including drafts and retired.
     *
     * @return list<TemplateRecord>
     */
    public function all(): array;

    /**
     * Only templates users may apply to a blog: PUBLISHED status, not deleted.
     *
     * @return list<TemplateRecord>
     */
    public function published(): array;
}
