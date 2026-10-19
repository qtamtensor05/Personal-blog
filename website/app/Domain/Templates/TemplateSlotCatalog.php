<?php

declare(strict_types=1);

namespace App\Domain\Templates;

use App\Domain\Templates\Enums\TemplateRegion;
use App\Domain\Templates\Enums\TemplateSection;

/**
 * The only components that can be put in a template.  This deliberately keeps
 * template definitions data-only: no user supplied HTML, CSS or JavaScript.
 */
final class TemplateSlotCatalog
{
    public static function defaultRegion(string $section): TemplateRegion
    {
        return match (TemplateSection::from($section)) {
            TemplateSection::Header, TemplateSection::Navigation => TemplateRegion::Header,
            TemplateSection::Footer => TemplateRegion::Footer,
            TemplateSection::Profile,
            TemplateSection::SidebarPosts,
            TemplateSection::Newsletter => TemplateRegion::Sidebar,
            default => TemplateRegion::Body,
        };
    }

    /** @return list<string> */
    public static function allowedRegions(string $section): array
    {
        return match (TemplateSection::from($section)) {
            TemplateSection::Header, TemplateSection::Navigation => [TemplateRegion::Header->value],
            TemplateSection::Footer => [TemplateRegion::Footer->value],
            TemplateSection::Profile, TemplateSection::SidebarPosts => [
                TemplateRegion::Sidebar->value,
            ],
            TemplateSection::Newsletter => [
                TemplateRegion::Body->value,
                TemplateRegion::Sidebar->value,
            ],
            default => [TemplateRegion::Body->value],
        };
    }

    /** @return list<string> */
    public static function fields(string $section): array
    {
        return match (TemplateSection::from($section)) {
            TemplateSection::Header => ['title', 'description', 'mediaId', 'ctaLabel', 'ctaUrl'],
            TemplateSection::Hero => [
                'eyebrow',
                'title',
                'description',
                'mediaId',
                'ctaLabel',
                'ctaUrl',
            ],
            TemplateSection::Image, TemplateSection::ImageText => [
                'title',
                'description',
                'mediaId',
                'alt',
                'ctaLabel',
                'ctaUrl',
            ],
            TemplateSection::Gallery => ['title', 'mediaIds'],
            TemplateSection::About, TemplateSection::Profile => ['title', 'description', 'mediaId'],
            TemplateSection::Cta => ['title', 'description', 'ctaLabel', 'ctaUrl'],
            TemplateSection::Newsletter => ['title', 'description', 'ctaLabel'],
            TemplateSection::RecentPosts => [
                'title',
                'archiveTitle',
                'archiveDescription',
                'categoryId',
                'limit',
            ],
            TemplateSection::Categories => ['title', 'categoryIds', 'limit'],
            TemplateSection::FeaturedPost => ['title'],
            TemplateSection::Footer => ['copyright', 'description'],
            default => [],
        };
    }
}
