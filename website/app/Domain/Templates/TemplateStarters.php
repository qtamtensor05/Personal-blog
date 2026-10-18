<?php

declare(strict_types=1);

namespace App\Domain\Templates;

use App\Domain\Templates\ValueObjects\TemplateData;
use App\Domain\Templates\ValueObjects\TemplateDesignTokens;
use App\Domain\Templates\ValueObjects\TemplatePostLayout;
use App\Domain\Templates\ValueObjects\TemplateSectionConfig;

final class TemplateStarters
{
    public static function blog(): TemplateData
    {
        return new TemplateData([
            new TemplateSectionConfig('site-header', 'header', [], 'header', 'Header'),
            new TemplateSectionConfig('hero', 'hero', [], 'body', 'Banner chính'),
            new TemplateSectionConfig('latest-posts', 'recent_posts', [], 'body', 'Bài viết mới'),
            new TemplateSectionConfig('categories', 'categories', [], 'body', 'Chuyên mục'),
            new TemplateSectionConfig('sidebar-profile', 'profile', [], 'sidebar', 'Giới thiệu blogger'),
            new TemplateSectionConfig('site-footer', 'footer', [], 'footer', 'Footer'),
        ], TemplateDesignTokens::default(), TemplatePostLayout::default());
    }
}
