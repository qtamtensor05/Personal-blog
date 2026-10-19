<?php

declare(strict_types=1);

namespace App\Infrastructure\Content\Persistence;

use App\Application\Content\Contracts\PageRepository;
use App\Application\Content\DTOs\PageRecord;
use App\Domain\Content\Enums\PostStatus;
use App\Domain\Content\ValueObjects\PostDraft;
use App\Domain\Content\ValueObjects\TiptapDocument;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

final class QueryBuilderPageRepository implements PageRepository
{
    private const TABLE = 'pages';

    public function find(int $blogId, int $id): ?PageRecord
    {
        $row = $this->base($blogId)->where('id', $id)->first();

        return $row === null ? null : $this->toDomain($row);
    }

    public function findPublishedBySlug(int $blogId, string $slug): ?PageRecord
    {
        $row = $this->base($blogId)
            ->where('slug', $slug)
            ->where('status', PostStatus::Published->value)
            ->first();

        return $row === null ? null : $this->toDomain($row);
    }

    public function forBlog(int $blogId): array
    {
        return $this->base($blogId)
            ->orderBy('title')
            ->get()
            ->map(fn ($row) => $this->toDomain($row))
            ->all();
    }

    public function publishedForBlog(int $blogId): array
    {
        return $this->base($blogId)
            ->where('status', PostStatus::Published->value)
            ->orderBy('title')
            ->get()
            ->map(fn ($row) => $this->toDomain($row))
            ->all();
    }

    public function slugExists(int $blogId, string $slug, ?int $exceptId = null): bool
    {
        // Unique indexes also cover soft-deleted rows.
        return DB::table(self::TABLE)
            ->where('blog_id', $blogId)
            ->where('slug', $slug)
            ->when($exceptId !== null, fn ($q) => $q->where('id', '!=', $exceptId))
            ->exists();
    }

    public function pageKeyExists(int $blogId, string $pageKey): bool
    {
        return DB::table(self::TABLE)
            ->where('blog_id', $blogId)
            ->where('page_key', $pageKey)
            ->exists();
    }

    public function create(
        int $blogId,
        int $actorId,
        string $slug,
        string $pageKey,
        PostDraft $draft,
    ): int {
        $now = now();

        return (int) DB::table(self::TABLE)->insertGetId([
            'blog_id' => $blogId,
            'title' => $draft->title,
            'slug' => $slug,
            'page_key' => $pageKey,
            'content_json' => json_encode($draft->content->toArray()),
            'draft_data' => json_encode($draft),
            'status' => PostStatus::Draft->value,
            'created_by' => $actorId,
            'updated_by' => $actorId,
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
        $row = $this->base($blogId)
            ->where('id', $id)
            ->first(['status', 'published_at']);

        // Never published: no live version to protect.
        if (
            $row !== null &&
            $row->published_at === null &&
            $row->status === PostStatus::Draft->value
        ) {
            $values += [
                'title' => $draft->title,
                'content_json' => json_encode($draft->content->toArray()),
            ];
        }

        $this->base($blogId)->where('id', $id)->update($values);
    }

    public function updateMeta(
        int $blogId,
        int $id,
        string $slug,
        ?string $seoTitle,
        ?string $seoDescription,
        int $updatedBy,
    ): void {
        $this->base($blogId)
            ->where('id', $id)
            ->update([
                'slug' => $slug,
                'seo_title' => $seoTitle,
                'seo_description' => $seoDescription,
                'updated_by' => $updatedBy,
                'updated_at' => now(),
            ]);
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
                    'content_json' => json_encode($draft->content->toArray()),
                    'draft_data' => null,
                ];
            }

            $this->base($blogId)->where('id', $id)->update($values);
        });
    }

    public function setStatus(int $blogId, int $id, PostStatus $status, int $updatedBy): void
    {
        $this->base($blogId)
            ->where('id', $id)
            ->update([
                'status' => $status->value,
                'updated_by' => $updatedBy,
                'updated_at' => now(),
            ]);
    }

    public function softDelete(int $blogId, int $id, int $updatedBy): void
    {
        $this->base($blogId)
            ->where('id', $id)
            ->update([
                'deleted_at' => now(),
                'updated_by' => $updatedBy,
                'updated_at' => now(),
            ]);
    }

    private function base(int $blogId): Builder
    {
        return DB::table(self::TABLE)->where('blog_id', $blogId)->whereNull('deleted_at');
    }

    private function toDomain(object $row): PageRecord
    {
        return new PageRecord(
            id: (int) $row->id,
            blogId: (int) $row->blog_id,
            title: $row->title,
            slug: $row->slug,
            pageKey: $row->page_key,
            content: TiptapDocument::fromArray(json_decode($row->content_json, true) ?? []),
            draft: $row->draft_data !== null
                ? PostDraft::fromArray(json_decode($row->draft_data, true))
                : null,
            status: PostStatus::from($row->status),
            seoTitle: $row->seo_title,
            seoDescription: $row->seo_description,
            publishedAt: $row->published_at,
            updatedAt: (string) $row->updated_at,
        );
    }
}
