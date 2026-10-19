<?php

declare(strict_types=1);

namespace App\Application\Blogs\UseCases\ViewPublicBlog;

use App\Application\BlogDesign\Contracts\BlogDesignRepository;
use App\Application\Blogs\Contracts\BlogRepository;
use App\Application\Blogs\Contracts\BlogSettingsRepository;
use App\Application\Blogs\DTOs\BlogSettingsRecord;
use App\Application\Blogs\Exceptions\BlogNotFound;
use App\Application\Content\Contracts\PageRepository;
use App\Application\Content\Contracts\PostRepository;
use App\Application\Content\Contracts\TaxonomyRepository;
use App\Domain\Blogs\Enums\BlogStatus;
use App\Domain\Blogs\Enums\BlogVisibility;
use App\Domain\Templates\TemplateLayouts;
use App\Domain\Templates\ValueObjects\TemplateDesignTokens;
use App\Domain\Templates\ValueObjects\TemplatePostLayout;
use App\Domain\Templates\ValueObjects\TemplateSectionConfig;

/**
 * Visitors see only PUBLISHED blogs that are PUBLIC or UNLISTED.
 * The owner may preview their own blog in any state except deleted.
 * Owners see their latest design draft at the same blog URL. Visitors can only
 * receive published_data, and never receive a draft snapshot.
 */
final readonly class ViewPublicBlogService
{
    public function __construct(
        private BlogRepository $blogs,
        private BlogSettingsRepository $settings,
        private BlogDesignRepository $designs,
        private PostRepository $posts,
        private PageRepository $pages,
        private TaxonomyRepository $taxonomy,
    ) {}

    public function execute(string $slug, ?int $viewerId): PublicBlogView
    {
        $blog = $this->blogs->findBySlug($slug);

        if ($blog === null) {
            throw new BlogNotFound;
        }

        $isOwner = $viewerId !== null && $viewerId === $blog->ownerUserId;
        $isPublic =
            $blog->status === BlogStatus::Published &&
            $blog->visibility !== BlogVisibility::Private;

        if (! $isPublic && ! $isOwner) {
            throw new BlogNotFound;
        }

        $designRecord = $this->designs->find($blog->id);
        $usingDraft = $isOwner && $designRecord?->draftData !== null;
        $design = $usingDraft ? $designRecord->draftData : $designRecord?->publishedData;

        // No published design yet: a minimal page with the platform default tokens.
        $sections = $design?->sections ?? [
            new TemplateSectionConfig('hero', 'hero'),
            new TemplateSectionConfig('footer', 'footer', [], 'footer'),
        ];

        $settings = $this->settings->find($blog->id) ?? BlogSettingsRecord::defaults($blog->id);
        $dynamicPostLimit = $settings->postsPerPage;
        foreach ($sections as $section) {
            if (in_array($section->section, ['recent_posts', 'sidebar_posts'], true)) {
                $dynamicPostLimit = max(
                    $dynamicPostLimit,
                    min(12, (int) ($design?->slotContent[$section->key]['limit'] ?? 6)),
                );
            }
        }

        return new PublicBlogView(
            blog: $blog,
            settings: $settings,
            sections: $sections,
            designTokens: $design?->designTokens ?? TemplateDesignTokens::default(),
            postLayout: $design?->postLayout ?? TemplatePostLayout::default(),
            noIndex: $usingDraft ||
                ! $isPublic ||
                $blog->visibility === BlogVisibility::Unlisted ||
                ! $blog->discoverable,
            isOwnerPreview: $usingDraft || ! $isPublic,
            // Only PUBLISHED posts; drafts and archived posts are never listed.
            posts: $this->posts->publishedForBlog($blog->id, $dynamicPostLimit),
            pages: $this->pages->publishedForBlog($blog->id),
            slotContent: $design?->slotContent ?? [],
            categories: $this->taxonomy->categories($blog->id),
            layout: $design?->layout ?? TemplateLayouts::DEFAULT,
        );
    }
}
