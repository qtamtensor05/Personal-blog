<?php

declare(strict_types=1);

namespace App\Infrastructure\Content\Persistence;

use App\Application\Content\Contracts\PublicPostCatalog;
use App\Application\Content\DTOs\PublicPostPage;
use App\Application\Content\DTOs\PublicPostSummary;
use App\Domain\Content\ValueObjects\TiptapDocument;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

final class QueryBuilderPublicPostCatalog implements PublicPostCatalog
{
    private const SUMMARY_COLUMNS = [
        'p.id',
        'p.blog_id',
        'p.cover_media_id',
        'p.content_json',
        'p.title',
        'p.slug',
        'p.excerpt',
        'p.published_at',
        'p.is_featured',
        'b.slug as blog_slug',
        'u.name as author_name',
        'u.id as author_user_id',
        'm.secure_url as cover_url',
    ];

    public function paginate(?string $search, int $page, int $perPage): PublicPostPage
    {
        $query = $this->query();

        if ($search !== null) {
            $term = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search).'%';
            $query->where(function (Builder $builder) use ($term): void {
                $builder->where('p.title', 'like', $term)->orWhere('p.excerpt', 'like', $term);
            });
        }

        $total = (clone $query)->count('p.id');
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = min($page, $lastPage);

        $items = $query
            ->select(self::SUMMARY_COLUMNS)
            ->orderByDesc('p.published_at')
            ->orderByDesc('p.id')
            ->forPage($page, $perPage)
            ->get()
            ->pipe(fn ($rows): array => $this->toSummaries($rows->all()));

        return new PublicPostPage($items, $total, $page, $perPage);
    }

    private function query(): Builder
    {
        return DB::table('posts as p')
            ->join('blogs as b', 'b.id', '=', 'p.blog_id')
            ->join('users as u', 'u.id', '=', 'p.author_user_id')
            ->leftJoin('media_assets as m', function ($join): void {
                $join
                    ->on('m.id', '=', 'p.cover_media_id')
                    ->where('m.status', '=', 'READY')
                    ->where('m.scope', 'BLOG')
                    ->on('m.blog_id', '=', 'p.blog_id')
                    ->whereNull('m.deleted_at');
            })
            ->where('p.status', 'PUBLISHED')
            ->whereNull('p.deleted_at')
            ->where('b.status', 'PUBLISHED')
            ->where('b.visibility', 'PUBLIC')
            ->where('b.discoverable', true)
            ->whereNull('b.deleted_at')
            ->whereNull('u.deleted_at');
    }

    public function featured(int $limit): array
    {
        return $this->query()
            ->where('p.is_featured', true)
            ->orderByDesc('p.published_at')
            ->orderByDesc('p.id')
            ->limit(max(0, $limit))
            ->select(self::SUMMARY_COLUMNS)
            ->get()
            ->pipe(fn ($rows): array => $this->toSummaries($rows->all()));
    }

    public function recent(int $limit, array $excludeIds = []): array
    {
        $query = $this->query();

        if ($excludeIds !== []) {
            $query->whereNotIn('p.id', $excludeIds);
        }

        return $query
            ->orderByDesc('p.published_at')
            ->orderByDesc('p.id')
            ->limit(max(0, $limit))
            ->select(self::SUMMARY_COLUMNS)
            ->get()
            ->pipe(fn ($rows): array => $this->toSummaries($rows->all()));
    }

    /** @param list<object> $rows
     * @return list<PublicPostSummary>
     */
    private function toSummaries(array $rows): array
    {
        $candidates = [];
        foreach ($rows as $row) {
            $bodyIds = TiptapDocument::fromArray(json_decode($row->content_json, true) ?? [])->mediaIds();
            $candidates[(int) $row->id] = array_values(array_unique(array_filter([$row->cover_media_id, ...$bodyIds])));
        }
        $ids = array_values(array_unique(array_merge([], ...array_values($candidates))));
        $assets = $ids === [] ? collect() : DB::table('media_assets')
            ->whereIn('id', $ids)
            ->where('scope', 'BLOG')->where('status', 'READY')->whereNull('deleted_at')
            ->whereNotNull('secure_url')->where('secure_url', '!=', '')
            ->get(['id', 'blog_id', 'secure_url'])->keyBy('id');
        foreach ($rows as $row) {
            $row->cover_url = null;
            foreach ($candidates[(int) $row->id] as $id) {
                $asset = $assets->get($id);
                if ($asset !== null && (int) $asset->blog_id === (int) $row->blog_id) {
                    $row->cover_url = $asset->secure_url;
                    break;
                }
            }
        }

        return array_map(fn (object $row): PublicPostSummary => $this->toSummary($row), $rows);
    }

    private function toSummary(object $row): PublicPostSummary
    {
        return new PublicPostSummary(
            id: (int) $row->id,
            title: $row->title,
            slug: $row->slug,
            blogSlug: $row->blog_slug,
            excerpt: $row->excerpt,
            coverUrl: $row->cover_url,
            publishedAt: $row->published_at,
            authorName: $row->author_name,
            authorUserId: (int) $row->author_user_id,
            isFeatured: (bool) $row->is_featured,
        );
    }
}
