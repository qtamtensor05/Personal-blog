<?php

declare(strict_types=1);

namespace App\Application\Blogs\DTOs;

final readonly class BlogSettingsRecord
{
    /**
     * @param  array<string, string>  $socialLinks  platform => URL
     */
    public function __construct(
        public int $blogId,
        public string $locale,
        public string $timezone,
        public ?int $logoMediaId,
        public ?int $faviconMediaId,
        public int $postsPerPage,
        public array $socialLinks,
        public ?string $seoTitle,
        public ?string $seoDescription,
        public ?int $ogMediaId,
        public int $storageQuotaBytes,
        public ?int $postLimit,
    ) {}

    /**
     * Values used when a blog has no blog_settings row yet (column defaults).
     */
    public static function defaults(int $blogId): self
    {
        return new self(
            blogId: $blogId,
            locale: 'vi',
            timezone: 'Asia/Ho_Chi_Minh',
            logoMediaId: null,
            faviconMediaId: null,
            postsPerPage: 10,
            socialLinks: [],
            seoTitle: null,
            seoDescription: null,
            ogMediaId: null,
            storageQuotaBytes: 104857600,
            postLimit: null,
        );
    }
}
