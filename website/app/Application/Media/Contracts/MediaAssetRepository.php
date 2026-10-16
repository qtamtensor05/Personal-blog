<?php

declare(strict_types=1);

namespace App\Application\Media\Contracts;

use App\Application\Media\DTOs\MediaAssetRecord;
use App\Application\Media\DTOs\StoredMedia;
use App\Domain\Media\Enums\MediaScope;

interface MediaAssetRepository
{
    /**
     * Insert a row in UPLOADING state before calling the storage.
     */
    public function createPending(
        MediaScope $scope,
        ?int $blogId,
        int $uploadedBy,
        string $cloudName,
        string $originalName,
        ?string $mimeType,
        ?string $altText,
    ): int;

    public function markReady(int $id, StoredMedia $stored): void;

    public function markFailed(int $id): void;

    public function find(int $id): ?MediaAssetRecord;

    /**
     * READY, not deleted, BLOG-scoped media that belongs to this blog.
     * FKs only check existence, so cross-blog references must be blocked here.
     */
    public function isUsableForBlog(int $id, int $blogId): bool;

    /**
     * Move READY/FAILED to DELETING. Returns false if another request got there first.
     */
    public function markDeleting(int $id): bool;

    public function markDeleted(int $id): void;

    /**
     * Restore READY after a failed remote delete.
     */
    public function markReadyAgain(int $id): void;

    /**
     * Bytes held by the blog's media that are not deleted.
     */
    public function usedBytesForBlog(int $blogId): int;

    public function quotaBytesForBlog(int $blogId): ?int;

    /**
     * Whether a column (avatar, logo, cover, OG, template preview, ...) still points at the media.
     */
    public function isReferenced(int $id): bool;
}
