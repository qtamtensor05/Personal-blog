<?php

declare(strict_types=1);

namespace App\Infrastructure\Content\Persistence;

use App\Application\Content\Contracts\PostRepository;
use App\Application\Content\DTOs\PostRecord;
use App\Domain\Content\Enums\PostStatus;
use App\Domain\Content\ValueObjects\PostDraft;
use App\Domain\Content\ValueObjects\TiptapDocument;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

final class QueryBuilderPostRepository implements PostRepository
{
    private const TABLE = 'posts';

    public function find(int $blogId, int $id): ?PostRecord
    {
        $row = $this->base($blogId)->where('id', $id)->first();

        return $row === null ? null : $this->toDomain($row, withTaxonomy: true);
    }

    public function findPublishedBySlug(int $blogId, string $slug): ?PostRecord
    {
        $row = $this->base($blogId)
            ->where('slug', $slug)
            ->where('status', PostStatus::Published->value)
            ->first();

        return $row === null ? null : $this->toDomain($row, withTaxonomy: true);
    }

    public function forBlog(int $blogId): array
    {
        return $this->base($blogId)
            ->orderByDesc('updated_at')
            ->get()
            ->map(fn ($row) => $this->toDomain($row))
            ->all();
    }

    public function publishedForBlog(int $blogId, int $limit): array
    {
        return $this->base($blogId)
            ->where('status', PostStatus::Published->value)
            ->orderByDesc('published_at')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => $this->toDomain($row))
            ->all();
    }

    public function countForBlog(int $blogId): int
    {
        return $this->base($blogId)->count();
    }

    public function slugExists(int $blogId, string $slug, ?int $exceptId = null): bool
    {
        // Unique index u_0 (blog_id, slug) also covers soft-deleted rows.
        return DB::table(self::TABLE)
            ->where('blog_id', $blogId)
            ->where('slug', $slug)
            ->when($exceptId !== null, fn ($q) => $q->where('id', '!=', $exceptId))
            ->exists();
    }

    public function create(int $blogId, int $authorId, string $slug, PostDraft $draft): int
    {
        $now = now();

        return (int) DB::table(self::TABLE)->insertGetId([
            'blog_id' => $blogId,
            'author_user_id' => $authorId,
            'title' => $draft->title,
            'slug' => $slug,
            'excerpt' => $draft->excerpt,
            'content_json' => json_encode($draft->content->toArray()),
            'draft_data' => json_encode($draft),
            'status' => PostStatus::Draft->value,
            'updated_by' => $authorId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function saveDraft(int $blogId, int $id, PostDraft $draft, int $updatedBy): void
    {
        $values = [
            'draft_data' => json_encode($draft),
            'updated_by' => $updatedBy,
            'updated_at' => now(),
        ];

        // A post never published has no live version to protect: keep live columns in sync.
        $row = $this->base($blogId)->where('id', $id)->first(['status', 'published_at']);

        if ($row !== null && $row->published_at === null && $row->status === PostStatus::Draft->value) {
            $values += [
                'title' => $draft->title,
                'excerpt' => $draft->excerpt,
                'content_json' => json_encode($draft->content->toArray()),
            ];
        }

        $this->base($blogId)->where('id', $id)->update($values);
    }

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
    ): void {
        DB::transaction(function () use (
            $blogId, $id, $slug, $coverMediaId, $ogMediaId, $seoTitle, $seoDescription,
            $isFeatured, $categoryIds, $tagIds, $updatedBy
        ): void {
            $now = now();

            $this->base($blogId)->where('id', $id)->update([
                'slug' => $slug,
                'cover_media_id' => $coverMediaId,
                'og_media_id' => $ogMediaId,
                'seo_title' => $seoTitle,
                'seo_description' => $seoDescription,
                'is_featured' => $isFeatured,
                'updated_by' => $updatedBy,
                'updated_at' => $now,
            ]);

            // Composite FKs (blog_id, post_id) / (blog_id, category_id) keep links inside the blog.
            DB::table('post_categories')->where('blog_id', $blogId)->where('post_id', $id)->delete();
            DB::table('post_categories')->insert(array_map(fn (int $categoryId) => [
                'blog_id' => $blogId, 'post_id' => $id, 'category_id' => $categoryId,
                'created_at' => $now, 'updated_at' => $now,
            ], $categoryIds));

            DB::table('post_tags')->where('blog_id', $blogId)->where('post_id', $id)->delete();
            DB::table('post_tags')->insert(array_map(fn (int $tagId) => [
                'blog_id' => $blogId, 'post_id' => $id, 'tag_id' => $tagId,
                'created_at' => $now, 'updated_at' => $now,
            ], $tagIds));
        });
    }

    public function publish(int $blogId, int $id, int $updatedBy): void
    {
        DB::transaction(function () use ($blogId, $id, $updatedBy): void {
            $row = $this->base($blogId)->where('id', $id)->lockForUpdate()->first();

            if ($row === null) {
                return;
            }

            $values = [
                'status' => PostStatus::Published->value,
                'published_at' => $row->published_at ?? now(),
                'scheduled_at' => null,
                'updated_by' => $updatedBy,
                'updated_at' => now(),
            ];

            if ($row->draft_data !== null) {
                $draft = PostDraft::fromArray(json_decode($row->draft_data, true));
                $values += [
                    'title' => $draft->title,
                    'excerpt' => $draft->excerpt,
                    'content_json' => json_encode($draft->content->toArray()),
                    'draft_data' => null,
                ];
            }

            $this->base($blogId)->where('id', $id)->update($values);
        });
    }

    public function setStatus(int $blogId, int $id, PostStatus $status, int $updatedBy): void
    {
        $this->base($blogId)->where('id', $id)->update([
            'status' => $status->value,
            'updated_by' => $updatedBy,
            'updated_at' => now(),
        ]);
    }

    public function softDelete(int $blogId, int $id, int $updatedBy): void
    {
        $this->base($blogId)->where('id', $id)->update([
            'deleted_at' => now(),
            'updated_by' => $updatedBy,
            'updated_at' => now(),
        ]);
    }

    private function base(int $blogId): Builder
    {
        return DB::table(self::TABLE)->where('blog_id', $blogId)->whereNull('deleted_at');
    }

    private function toDomain(object $row, bool $withTaxonomy = false): PostRecord
    {
        $id = (int) $row->id;

        return new PostRecord(
            id: $id,
            blogId: (int) $row->blog_id,
            authorUserId: (int) $row->author_user_id,
            title: $row->title,
            slug: $row->slug,
            excerpt: $row->excerpt,
            content: TiptapDocument::fromArray(json_decode($row->content_json, true) ?? []),
            draft: $row->draft_data !== null ? PostDraft::fromArray(json_decode($row->draft_data, true)) : null,
            status: PostStatus::from($row->status),
            coverMediaId: $row->cover_media_id !== null ? (int) $row->cover_media_id : null,
            ogMediaId: $row->og_media_id !== null ? (int) $row->og_media_id : null,
            seoTitle: $row->seo_title,
            seoDescription: $row->seo_description,
            isFeatured: (bool) $row->is_featured,
            publishedAt: $row->published_at,
            updatedAt: (string) $row->updated_at,
            categoryIds: $withTaxonomy
                ? DB::table('post_categories')->where('post_id', $id)->pluck('category_id')->map(fn ($v) => (int) $v)->all()
                : [],
            tagIds: $withTaxonomy
                ? DB::table('post_tags')->where('post_id', $id)->pluck('tag_id')->map(fn ($v) => (int) $v)->all()
                : [],
        );
    }
}
