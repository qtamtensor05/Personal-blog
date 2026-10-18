<?php

declare(strict_types=1);

namespace App\Application\Blogs\UseCases\ViewPublicBlog;

use App\Application\Blogs\DTOs\BlogRecord;
use App\Application\Blogs\DTOs\BlogSettingsRecord;
use App\Application\Content\DTOs\PageRecord;
use App\Application\Content\DTOs\PostRecord;
use App\Domain\Templates\ValueObjects\TemplateDesignTokens;
use App\Domain\Templates\ValueObjects\TemplatePostLayout;
use App\Domain\Templates\ValueObjects\TemplateSectionConfig;

final readonly class PublicBlogView
{
    /**
     * @param  list<TemplateSectionConfig>  $sections
     * @param  list<PostRecord>  $posts  Latest PUBLISHED posts.
     * @param  list<PageRecord>  $pages  PUBLISHED static pages.
     */
    public function __construct(
        public BlogRecord $blog,
        public BlogSettingsRecord $settings,
        public array $sections,
        public TemplateDesignTokens $designTokens,
        public TemplatePostLayout $postLayout,
        // UNLISTED, not discoverable, or owner preview of a non-public blog.
        public bool $noIndex,
        public bool $isOwnerPreview,
        public array $posts = [],
        public array $pages = [],
    ) {}
}
