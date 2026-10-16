<?php

declare(strict_types=1);

namespace App\Application\Blogs\UseCases\UpdateBlogSettings;

use App\Application\Blogs\Contracts\BlogRepository;
use App\Application\Blogs\Contracts\BlogSettingsRepository;
use App\Application\Blogs\Exceptions\BlogNotFound;
use App\Application\Media\Contracts\MediaAssetRepository;
use DomainException;

final readonly class UpdateBlogSettingsService
{
    public function __construct(
        private BlogRepository $blogs,
        private BlogSettingsRepository $settings,
        private MediaAssetRepository $media,
    ) {}

    /**
     * @param  array<string, string>  $socialLinks
     */
    public function execute(
        int $actorId,
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
        $blog = $this->blogs->find($blogId);

        if ($blog === null || $blog->ownerUserId !== $actorId) {
            throw new BlogNotFound;
        }

        // Mirrors ck_blog_settings_0.
        if ($postsPerPage < 1 || $postsPerPage > 100) {
            throw new DomainException('Posts per page must be between 1 and 100.');
        }

        foreach (['logo' => $logoMediaId, 'favicon' => $faviconMediaId, 'OG image' => $ogMediaId] as $label => $id) {
            if ($id !== null && ! $this->media->isUsableForBlog($id, $blogId)) {
                throw new DomainException("The selected {$label} is not an image of this blog.");
            }
        }

        $this->settings->save(
            $blogId,
            $locale,
            $timezone,
            $logoMediaId,
            $faviconMediaId,
            $postsPerPage,
            $socialLinks,
            $seoTitle,
            $seoDescription,
            $ogMediaId,
        );
    }
}
