<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Infrastructure\Templates\Persistence\TemplateDataHydrator;
use PHPUnit\Framework\TestCase;

final class TemplateDataHydratorTest extends TestCase
{
    public function test_it_converts_the_legacy_template_schema(): void
    {
        $legacy = json_encode(
            [
                'project' => [
                    'pages' => [
                        [
                            'id' => 'home',
                            'sections' => [
                                [
                                    'id' => 'lead',
                                    'type' => 'text',
                                    'content' => 'Legacy introduction',
                                ],
                                ['id' => 'posts', 'type' => 'post-list'],
                            ],
                        ],
                    ],
                ],
                'navigation' => ['homepage' => 'home'],
                'schemaVersion' => 1,
                'themeDefaults' => ['accent' => '#23533b'],
            ],
            JSON_THROW_ON_ERROR,
        );

        $data = (new TemplateDataHydrator)->fromJson($legacy);

        self::assertCount(2, $data->sections);
        self::assertSame('about', $data->sections[0]->section);
        self::assertSame('Legacy introduction', $data->sections[0]->props['content']);
        self::assertSame('recent_posts', $data->sections[1]->section);
        self::assertSame('#23533b', $data->designTokens->accentColor);
        self::assertSame('editorial', $data->postLayout->variant);
        self::assertTrue($data->postLayout->showCover);
    }

    public function test_it_keeps_the_current_template_schema(): void
    {
        $current = json_encode(
            [
                'sections' => [['key' => 'hero', 'section' => 'hero', 'props' => []]],
                'designTokens' => [
                    'primaryColor' => '#90B250',
                    'secondaryColor' => '#BD927D',
                    'accentColor' => '#E0BE75',
                    'textColor' => '#616B5E',
                    'backgroundColor' => '#F7F6F1',
                    'headingFont' => 'Instrument Sans',
                    'bodyFont' => 'Instrument Sans',
                ],
                'postLayout' => [
                    'variant' => 'card',
                    'contentWidth' => 'wide',
                    'showBlogName' => false,
                    'showPublishedDate' => true,
                    'showCover' => false,
                ],
            ],
            JSON_THROW_ON_ERROR,
        );

        $data = (new TemplateDataHydrator)->fromJson($current);

        self::assertCount(1, $data->sections);
        self::assertSame('hero', $data->sections[0]->section);
        self::assertSame('card', $data->postLayout->variant);
        self::assertSame('wide', $data->postLayout->contentWidth);
        self::assertFalse($data->postLayout->showBlogName);
        self::assertFalse($data->postLayout->showCover);
        self::assertSame('default', $data->layout);
    }
}
