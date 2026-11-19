<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Application\Content\DTOs\PostWritingSuggestion;
use App\Domain\Content\ValueObjects\PostDraft;
use App\Domain\Content\ValueObjects\PostDraftMetadata;
use App\Domain\Content\ValueObjects\TiptapDocument;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class PostWritingSuggestionTest extends TestCase
{
    private function response(): array
    {
        return ['title' => 'A title', 'slug' => 'a-title', 'excerpt' => 'Summary', 'seo_title' => 'SEO title',
            'seo_description' => 'SEO description', 'image_alt' => 'A photo', 'blocks' => [
                ['type' => 'heading', 'text' => 'Section'],
                ['type' => 'paragraph', 'text' => '<script>alert(1)</script>'],
                ['type' => 'bulletList', 'items' => ['One', 'Two']],
            ]];
    }

    public function test_it_maps_structured_ai_fields_to_safe_rich_content(): void
    {
        $result = PostWritingSuggestion::fromArray($this->response())->toArray();
        self::assertSame('a-title', $result['slug']);
        self::assertSame('SEO title', $result['seo_title']);
        self::assertSame(['level' => 2], $result['content']['content'][0]['attrs']);
        self::assertSame('<script>alert(1)</script>', $result['content']['content'][1]['content'][0]['text']);
        self::assertSame('listItem', $result['content']['content'][2]['content'][0]['type']);
    }

    public function test_it_rejects_invalid_slugs(): void
    {
        $data = $this->response();
        $data['slug'] = 'Invalid slug!';
        $this->expectException(InvalidArgumentException::class);
        PostWritingSuggestion::fromArray($data);
    }

    public function test_it_rejects_oversized_fields(): void
    {
        $data = $this->response();
        $data['seo_description'] = str_repeat('x', 501);
        $this->expectException(InvalidArgumentException::class);
        PostWritingSuggestion::fromArray($data);
    }

    public function test_it_rejects_styles_and_unexpected_content_blocks(): void
    {
        $data = $this->response();
        $data['blocks'] = [['type' => 'style', 'text' => 'body{display:none}']];
        $this->expectException(InvalidArgumentException::class);
        PostWritingSuggestion::fromArray($data);
    }

    public function test_draft_metadata_round_trips_without_changing_legacy_drafts(): void
    {
        $old = new PostDraft('Title', null, TiptapDocument::empty());
        self::assertArrayNotHasKey('metadata', $old->jsonSerialize());
        self::assertNull(PostDraft::fromArray($old->jsonSerialize())->metadata);
        $draft = new PostDraft('Title', 'Summary', TiptapDocument::empty(), new PostDraftMetadata('draft-slug', 'Draft SEO', 'Draft description'));
        $restored = PostDraft::fromArray(json_decode(json_encode($draft), true));
        self::assertSame('draft-slug', $restored->metadata->slug);
        self::assertSame('Draft SEO', $restored->metadata->seoTitle);
        self::assertSame('Draft description', $restored->metadata->seoDescription);
    }
}
