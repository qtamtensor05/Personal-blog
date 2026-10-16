<?php

declare(strict_types=1);

namespace App\Application\Content\UseCases\ManageTaxonomy;

use App\Application\BlogDesign\Services\OwnedBlogGuard;
use App\Application\Content\Contracts\TaxonomyRepository;
use DomainException;

/**
 * Categories and tags of a blog. Owner-only; every operation is scoped by blog_id.
 */
final readonly class ManageTaxonomyService
{
    public function __construct(
        private OwnedBlogGuard $guard,
        private TaxonomyRepository $taxonomy,
    ) {}

    public function createCategory(int $actorId, int $blogId, string $name, string $slug, ?string $description, ?int $parentId): int
    {
        $this->guard->ensure($actorId, $blogId);
        $this->ensureCategorySlugFree($blogId, $slug);
        $this->ensureParentValid($blogId, null, $parentId);

        return $this->taxonomy->createCategory($blogId, $name, $slug, $description, $parentId);
    }

    public function updateCategory(
        int $actorId,
        int $blogId,
        int $categoryId,
        string $name,
        string $slug,
        ?string $description,
        ?int $parentId,
        bool $isActive,
    ): void {
        $this->guard->ensure($actorId, $blogId);
        $this->requireCategory($blogId, $categoryId);
        $this->ensureCategorySlugFree($blogId, $slug, $categoryId);
        $this->ensureParentValid($blogId, $categoryId, $parentId);

        $this->taxonomy->updateCategory($blogId, $categoryId, $name, $slug, $description, $parentId, $isActive);
    }

    public function deleteCategory(int $actorId, int $blogId, int $categoryId): void
    {
        $this->guard->ensure($actorId, $blogId);
        $this->requireCategory($blogId, $categoryId);

        // parent_id FK is RESTRICT; ask the owner to move children first.
        if ($this->taxonomy->categoryHasChildren($blogId, $categoryId)) {
            throw new DomainException('Move or delete the subcategories first.');
        }

        $this->taxonomy->deleteCategory($blogId, $categoryId);
    }

    public function createTag(int $actorId, int $blogId, string $name, string $slug): int
    {
        $this->guard->ensure($actorId, $blogId);

        if ($this->taxonomy->tagSlugExists($blogId, $slug)) {
            throw new DomainException('A tag with this slug already exists.');
        }

        return $this->taxonomy->createTag($blogId, $name, $slug);
    }

    public function updateTag(int $actorId, int $blogId, int $tagId, string $name, string $slug): void
    {
        $this->guard->ensure($actorId, $blogId);

        if ($this->taxonomy->findTag($blogId, $tagId) === null) {
            throw new DomainException('Tag not found.');
        }

        if ($this->taxonomy->tagSlugExists($blogId, $slug, $tagId)) {
            throw new DomainException('A tag with this slug already exists.');
        }

        $this->taxonomy->updateTag($blogId, $tagId, $name, $slug);
    }

    public function deleteTag(int $actorId, int $blogId, int $tagId): void
    {
        $this->guard->ensure($actorId, $blogId);

        if ($this->taxonomy->findTag($blogId, $tagId) === null) {
            throw new DomainException('Tag not found.');
        }

        $this->taxonomy->deleteTag($blogId, $tagId);
    }

    private function requireCategory(int $blogId, int $categoryId): void
    {
        if ($this->taxonomy->findCategory($blogId, $categoryId) === null) {
            throw new DomainException('Category not found.');
        }
    }

    private function ensureCategorySlugFree(int $blogId, string $slug, ?int $exceptId = null): void
    {
        if ($this->taxonomy->categorySlugExists($blogId, $slug, $exceptId)) {
            throw new DomainException('A category with this slug already exists.');
        }
    }

    /**
     * Parent must belong to the same blog and must not create a cycle.
     */
    private function ensureParentValid(int $blogId, ?int $categoryId, ?int $parentId): void
    {
        $current = $parentId;
        $seen = [];

        while ($current !== null) {
            if ($current === $categoryId || isset($seen[$current])) {
                throw new DomainException('A category cannot be nested inside itself.');
            }

            $seen[$current] = true;
            $parent = $this->taxonomy->findCategory($blogId, $current);

            if ($parent === null) {
                throw new DomainException('Parent category not found in this blog.');
            }

            $current = $parent->parentId;
        }
    }
}
