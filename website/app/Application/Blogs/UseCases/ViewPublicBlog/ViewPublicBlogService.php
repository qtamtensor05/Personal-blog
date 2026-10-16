<?php

declare(strict_types=1);

namespace App\Application\Blogs\UseCases\ViewPublicBlog;

use App\Application\BlogDesign\Contracts\BlogDesignRepository;
use App\Application\Blogs\Contracts\BlogRepository;
use App\Application\Blogs\Contracts\BlogSettingsRepository;
use App\Application\Blogs\DTOs\BlogSettingsRecord;
use App\Application\Blogs\Exceptions\BlogNotFound;
use App\Domain\Blogs\Enums\BlogStatus;
use App\Domain\Blogs\Enums\BlogVisibility;
use App\Domain\Templates\ValueObjects\TemplateDesignTokens;
use App\Domain\Templates\ValueObjects\TemplateSectionConfig;

/**
 * Visitors see only PUBLISHED blogs that are PUBLIC or UNLISTED.
 * The owner may preview their own blog in any state except deleted.
 * Only published_data is rendered; drafts never reach the public page.
 */
final readonly class ViewPublicBlogService
{
    public function __construct(
        private BlogRepository $blogs,
        private BlogSettingsRepository $settings,
        private BlogDesignRepository $designs,
    ) {}

    public function execute(string $slug, ?int $viewerId): PublicBlogView
    {
        $blog = $this->blogs->findBySlug($slug);

        if ($blog === null) {
            throw new BlogNotFound;
        }

        $isOwner = $viewerId !== null && $viewerId === $blog->ownerUserId;
        $isPublic = $blog->status === BlogStatus::Published && $blog->visibility !== BlogVisibility::Private;

        if (! $isPublic && ! $isOwner) {
            throw new BlogNotFound;
        }

        $design = $this->designs->find($blog->id)?->publishedData;

        // No published design yet: a minimal page with the platform default tokens.
        $sections = $design?->sections ?? [
            new TemplateSectionConfig('hero', 'hero'),
            new TemplateSectionConfig('footer', 'footer'),
        ];

        return new PublicBlogView(
            blog: $blog,
            settings: $this->settings->find($blog->id) ?? BlogSettingsRecord::defaults($blog->id),
            sections: $sections,
            designTokens: $design?->designTokens ?? TemplateDesignTokens::default(),
            noIndex: ! $isPublic || $blog->visibility === BlogVisibility::Unlisted || ! $blog->discoverable,
            isOwnerPreview: ! $isPublic,
        );
    }
}
