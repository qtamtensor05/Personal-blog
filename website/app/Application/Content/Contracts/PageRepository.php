<?php

declare(strict_types=1);

namespace App\Application\Content\Contracts;

use App\Application\Content\DTOs\PageRecord;
use App\Domain\Content\Enums\PostStatus;
use App\Domain\Content\ValueObjects\PostDraft;

/**
 * Static pages of one blog; every method is scoped by blog_id.
 * Same live/draft split as posts: content_json is live, draft_data holds unpublished edits.
 */
interface PageRepository
{
    public function find(int $blogId, int $id): ?PageRecord;

    public function findPublishedBySlug(int $blogId, string $slug): ?PageRecord;

    /** @return list<PageRecord> */
    public function forBlog(int $blogId): array;

    /** @return list<PageRecord> */
    public function publishedForBlog(int $blogId): array;

    public function slugExists(int $blogId, string $slug, ?int $exceptId = null): bool;

    public function pageKeyExists(int $blogId, string $pageKey): bool;

    public function create(
        int $blogId,
        int $actorId,
        string $slug,
        string $pageKey,
        PostDraft $draft,
    ): int;

    public function saveDraft(int $blogId, int $id, PostDraft $draft, int $updatedBy): void;

    public function updateMeta(
        int $blogId,
        int $id,
        string $slug,
        ?string $seoTitle,
        ?string $seoDescription,
        int $updatedBy,
    ): void;

    public function publish(int $blogId, int $id, int $updatedBy): void;

    public function setStatus(int $blogId, int $id, PostStatus $status, int $updatedBy): void;

    public function softDelete(int $blogId, int $id, int $updatedBy): void;
}
