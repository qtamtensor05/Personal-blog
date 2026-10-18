<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domain\BlogDesign\ValueObjects\BlogDesignData;
use App\Domain\Templates\TemplateLayouts;
use App\Domain\Templates\TemplateSlotCatalog;
use App\Domain\Templates\TemplateStarters;
use App\Domain\Templates\ValueObjects\TemplateSectionConfig;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class TemplateSlotConfigurationTest extends TestCase
{
    public function test_starter_has_fixed_page_regions(): void
    {
        $regions = array_map(fn (TemplateSectionConfig $slot) => $slot->region, TemplateStarters::blog()->sections);

        self::assertContains('header', $regions);
        self::assertContains('body', $regions);
        self::assertContains('sidebar', $regions);
        self::assertContains('footer', $regions);
    }

    public function test_component_cannot_be_placed_in_a_wrong_region(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new TemplateSectionConfig('bad-footer', 'footer', [], 'body');
    }

    public function test_minimal_public_fallback_uses_the_footer_region(): void
    {
        $footer = new TemplateSectionConfig('footer', 'footer', [], 'footer');

        self::assertSame('footer', $footer->region);
    }

    public function test_lifestyle_starter_has_a_fixed_layout_and_fallback_content(): void
    {
        $template = TemplateStarters::lifestyleEditorial();
        $slots = [];
        foreach ($template->sections as $slot) {
            $slots[$slot->key] = $slot;
        }

        self::assertSame(TemplateLayouts::LIFESTYLE_EDITORIAL, $template->layout);
        self::assertSame('My Life. My Blog.', $slots['hero']->props['title']);
        self::assertSame('Tất cả bài viết', $slots['latest-posts']->props['archiveTitle']);
        self::assertSame('sidebar', $slots['sidebar-profile']->region);
        self::assertSame('footer', $slots['site-footer']->region);
    }

    public function test_recent_posts_slot_allows_home_and_archive_configuration(): void
    {
        self::assertSame(
            ['title', 'archiveTitle', 'archiveDescription', 'categoryId', 'limit'],
            TemplateSlotCatalog::fields('recent_posts'),
        );
    }

    public function test_blog_slot_content_is_serialized_separately_from_template_slots(): void
    {
        $template = TemplateStarters::blog();
        $design = BlogDesignData::fromTemplate(1, 1, $template)
            ->withSlotContent(['hero' => ['title' => 'A safe, fixed hero']]);

        self::assertTrue($design->hasSlotContent());
        self::assertSame('A safe, fixed hero', $design->jsonSerialize()['slotContent']['hero']['title']);
        self::assertSame('hero', $design->sections[1]->section);
        self::assertSame('default', $design->layout);
    }
}
