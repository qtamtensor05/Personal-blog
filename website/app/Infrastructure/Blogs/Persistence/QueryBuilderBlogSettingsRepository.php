<?php

declare(strict_types=1);

namespace App\Infrastructure\Blogs\Persistence;

use App\Application\Blogs\Contracts\BlogSettingsRepository;
use App\Application\Blogs\DTOs\BlogSettingsRecord;
use Illuminate\Support\Facades\DB;

final class QueryBuilderBlogSettingsRepository implements BlogSettingsRepository
{
    private const TABLE = 'blog_settings';

    public function find(int $blogId): ?BlogSettingsRecord
    {
        $row = DB::table(self::TABLE)->where('blog_id', $blogId)->first();

        if ($row === null) {
            return null;
        }

        $links = $row->social_links !== null ? json_decode($row->social_links, true) : [];

        return new BlogSettingsRecord(
            blogId: (int) $row->blog_id,
            locale: $row->locale,
            timezone: $row->timezone,
            logoMediaId: $row->logo_media_id !== null ? (int) $row->logo_media_id : null,
            faviconMediaId: $row->favicon_media_id !== null ? (int) $row->favicon_media_id : null,
            postsPerPage: (int) $row->posts_per_page,
            socialLinks: is_array($links) ? $links : [],
            seoTitle: $row->seo_title,
            seoDescription: $row->seo_description,
            ogMediaId: $row->og_media_id !== null ? (int) $row->og_media_id : null,
            storageQuotaBytes: (int) $row->storage_quota_bytes,
            postLimit: $row->post_limit !== null ? (int) $row->post_limit : null,
        );
    }

    public function save(
        int $blogId,
        string $locale,
        string $timezone,
        ?int $logoMediaId,
        ?int $faviconMediaId,
        int $postsPerPage,
        array $socialLinks,
        ?string $seoTitle,
        ?string $seoDescription,
        ?int $ogMediaId,
    ): void {
        $now = now();
        $values = [
            'locale' => $locale,
            'timezone' => $timezone,
            'logo_media_id' => $logoMediaId,
            'favicon_media_id' => $faviconMediaId,
            'posts_per_page' => $postsPerPage,
            'social_links' => $socialLinks === [] ? null : json_encode($socialLinks),
            'seo_title' => $seoTitle,
            'seo_description' => $seoDescription,
            'og_media_id' => $ogMediaId,
            'updated_at' => $now,
        ];

        // Quota and post_limit keep their column defaults on insert.
        DB::table(self::TABLE)->upsert(
            ['blog_id' => $blogId, 'created_at' => $now] + $values,
            ['blog_id'],
            array_keys($values),
        );
    }
}
