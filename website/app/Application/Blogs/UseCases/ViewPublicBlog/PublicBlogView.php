<?php

declare(strict_types=1);

namespace App\Application\Blogs\UseCases\ViewPublicBlog;

use App\Application\Blogs\DTOs\BlogRecord;
use App\Application\Blogs\DTOs\BlogSettingsRecord;
use App\Domain\Templates\ValueObjects\TemplateDesignTokens;
use App\Domain\Templates\ValueObjects\TemplateSectionConfig;

final readonly class PublicBlogView
{
    /**
     * @param  list<TemplateSectionConfig>  $sections
     */
    public function __construct(
        public BlogRecord $blog,
        public BlogSettingsRecord $settings,
        public array $sections,
        public TemplateDesignTokens $designTokens,
        // UNLISTED, not discoverable, or owner preview of a non-public blog.
        public bool $noIndex,
        public bool $isOwnerPreview,
    ) {}
}
