<?php

declare(strict_types=1);

namespace App\Application\Content\Contracts;

use App\Application\Content\DTOs\CategoryRecord;
use App\Application\Content\DTOs\TagRecord;

/**
 * Categories and tags of one blog. Every method is scoped by blog_id so rows of
 * another blog can never be read or changed (matches the composite FKs).
 */
interface TaxonomyRepository
{
    /** @return list<CategoryRecord> */
    public function categories(int $blogId): array;

    public function findCategory(int $blogId, int $id): ?CategoryRecord;

    public function categorySlugExists(int $blogId, string $slug, ?int $exceptId = null): bool;

    public function createCategory(
        int $blogId,
        string $name,
        string $slug,
        ?string $description,
        ?int $parentId,
    ): int;

    public function updateCategory(
        int $blogId,
        int $id,
        string $name,
        string $slug,
        ?string $description,
        ?int $parentId,
        bool $isActive,
    ): void;

    public function categoryHasChildren(int $blogId, int $id): bool;

    /**
     * Removes post_categories links, then the category.
     */
    public function deleteCategory(int $blogId, int $id): void;

    /** @return list<TagRecord> */
    public function tags(int $blogId): array;

    public function findTag(int $blogId, int $id): ?TagRecord;

    public function tagSlugExists(int $blogId, string $slug, ?int $exceptId = null): bool;

    public function createTag(int $blogId, string $name, string $slug): int;

    public function updateTag(int $blogId, int $id, string $name, string $slug): void;

    /**
     * Removes post_tags links, then the tag.
     */
    public function deleteTag(int $blogId, int $id): void;
}
