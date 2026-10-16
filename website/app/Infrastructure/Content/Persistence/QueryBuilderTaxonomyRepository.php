<?php

declare(strict_types=1);

namespace App\Infrastructure\Content\Persistence;

use App\Application\Content\Contracts\TaxonomyRepository;
use App\Application\Content\DTOs\CategoryRecord;
use App\Application\Content\DTOs\TagRecord;
use Illuminate\Support\Facades\DB;

final class QueryBuilderTaxonomyRepository implements TaxonomyRepository
{
    public function categories(int $blogId): array
    {
        return DB::table('categories')
            ->where('blog_id', $blogId)
            ->orderBy('name')
            ->get()
            ->map(fn ($row) => $this->toCategory($row))
            ->all();
    }

    public function findCategory(int $blogId, int $id): ?CategoryRecord
    {
        $row = DB::table('categories')->where('blog_id', $blogId)->where('id', $id)->first();

        return $row === null ? null : $this->toCategory($row);
    }

    public function categorySlugExists(int $blogId, string $slug, ?int $exceptId = null): bool
    {
        return DB::table('categories')
            ->where('blog_id', $blogId)
            ->where('slug', $slug)
            ->when($exceptId !== null, fn ($q) => $q->where('id', '!=', $exceptId))
            ->exists();
    }

    public function createCategory(int $blogId, string $name, string $slug, ?string $description, ?int $parentId): int
    {
        $now = now();

        return (int) DB::table('categories')->insertGetId([
            'blog_id' => $blogId,
            'name' => $name,
            'slug' => $slug,
            'description' => $description,
            'parent_id' => $parentId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function updateCategory(
        int $blogId,
        int $id,
        string $name,
        string $slug,
        ?string $description,
        ?int $parentId,
        bool $isActive,
    ): void {
        DB::table('categories')
            ->where('blog_id', $blogId)
            ->where('id', $id)
            ->update([
                'name' => $name,
                'slug' => $slug,
                'description' => $description,
                'parent_id' => $parentId,
                'is_active' => $isActive,
                'updated_at' => now(),
            ]);
    }

    public function categoryHasChildren(int $blogId, int $id): bool
    {
        return DB::table('categories')->where('blog_id', $blogId)->where('parent_id', $id)->exists();
    }

    public function deleteCategory(int $blogId, int $id): void
    {
        DB::transaction(function () use ($blogId, $id): void {
            DB::table('post_categories')->where('blog_id', $blogId)->where('category_id', $id)->delete();
            DB::table('categories')->where('blog_id', $blogId)->where('id', $id)->delete();
        });
    }

    public function tags(int $blogId): array
    {
        return DB::table('tags')
            ->where('blog_id', $blogId)
            ->orderBy('name')
            ->get()
            ->map(fn ($row) => $this->toTag($row))
            ->all();
    }

    public function findTag(int $blogId, int $id): ?TagRecord
    {
        $row = DB::table('tags')->where('blog_id', $blogId)->where('id', $id)->first();

        return $row === null ? null : $this->toTag($row);
    }

    public function tagSlugExists(int $blogId, string $slug, ?int $exceptId = null): bool
    {
        return DB::table('tags')
            ->where('blog_id', $blogId)
            ->where('slug', $slug)
            ->when($exceptId !== null, fn ($q) => $q->where('id', '!=', $exceptId))
            ->exists();
    }

    public function createTag(int $blogId, string $name, string $slug): int
    {
        $now = now();

        return (int) DB::table('tags')->insertGetId([
            'blog_id' => $blogId,
            'name' => $name,
            'slug' => $slug,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function updateTag(int $blogId, int $id, string $name, string $slug): void
    {
        DB::table('tags')
            ->where('blog_id', $blogId)
            ->where('id', $id)
            ->update(['name' => $name, 'slug' => $slug, 'updated_at' => now()]);
    }

    public function deleteTag(int $blogId, int $id): void
    {
        DB::transaction(function () use ($blogId, $id): void {
            DB::table('post_tags')->where('blog_id', $blogId)->where('tag_id', $id)->delete();
            DB::table('tags')->where('blog_id', $blogId)->where('id', $id)->delete();
        });
    }

    private function toCategory(object $row): CategoryRecord
    {
        return new CategoryRecord(
            id: (int) $row->id,
            blogId: (int) $row->blog_id,
            name: $row->name,
            slug: $row->slug,
            description: $row->description,
            parentId: $row->parent_id !== null ? (int) $row->parent_id : null,
            isActive: (bool) $row->is_active,
        );
    }

    private function toTag(object $row): TagRecord
    {
        return new TagRecord(
            id: (int) $row->id,
            blogId: (int) $row->blog_id,
            name: $row->name,
            slug: $row->slug,
        );
    }
}
