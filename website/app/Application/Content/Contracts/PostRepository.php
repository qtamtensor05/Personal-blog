<?php

declare(strict_types=1);

namespace App\Application\Content\Contracts;

use App\Application\Content\DTOs\PostRecord;
use App\Domain\Content\Enums\PostStatus;
use App\Domain\Content\ValueObjects\PostDraft;

/**
 * Posts of one blog. Every method is scoped by blog_id.
 *
 * posts.title / excerpt / content_json hold the live version;
 * posts.draft_data holds unpublished edits (PostDraft).
 */
interface PostRepository
{
    public function find(int $blogId, int $id): ?PostRecord;

    public function findPublishedBySlug(int $blogId, string $slug): ?PostRecord;

    /** @return list<PostRecord> */
    public function forBlog(int $blogId): array;

    /** @return list<PostRecord> */
    public function publishedForBlog(int $blogId, int $limit): array;

    /** @return list<PostRecord> */
    public function publicSummariesForBlog(int $blogId, ?int $limit, ?string $search = null): array;

    public function countForBlog(int $blogId): int;

    public function slugExists(int $blogId, string $slug, ?int $exceptId = null): bool;

    /**
     * New DRAFT post; the draft is also written to the live columns (required, but not visible while DRAFT).
     */
    public function create(int $blogId, int $authorId, string $slug, PostDraft $draft): int;

    public function saveDraft(int $blogId, int $id, PostDraft $draft, int $updatedBy): void;

    /**
     * Metadata is applied directly (not drafted) in the MVP.
     *
     * @param  list<int>  $categoryIds
     * @param  list<int>  $tagIds
     */
    public function updateMeta(
        int $blogId,
        int $id,
        string $slug,
        ?int $coverMediaId,
        ?int $ogMediaId,
        ?string $seoTitle,
        ?string $seoDescription,
        bool $isFeatured,
        array $categoryIds,
        array $tagIds,
        int $updatedBy,
    ): void;

    /**
     * Copy draft_data into the live columns, clear the draft, set PUBLISHED and first published_at.
     */
    public function publish(int $blogId, int $id, int $updatedBy): void;

    public function setStatus(int $blogId, int $id, PostStatus $status, int $updatedBy): void;

    public function softDelete(int $blogId, int $id, int $updatedBy): void;

    public function restore(int $blogId, int $id): void;

    public function permanentlyDelete(int $blogId, int $id): void;

    /** @return list<PostRecord> */
    public function deletedForBlog(int $blogId): array;
}
