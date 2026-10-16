<?php

declare(strict_types=1);

namespace App\Application\Blogs\Contracts;

use App\Application\Blogs\DTOs\BlogSettingsRecord;

interface BlogSettingsRepository
{
    public function find(int $blogId): ?BlogSettingsRecord;

    /**
     * Insert or update the owner-editable columns.
     * storage_quota_bytes and post_limit are platform limits and are never written here.
     *
     * @param  array<string, string>  $socialLinks
     */
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
    ): void;
}
