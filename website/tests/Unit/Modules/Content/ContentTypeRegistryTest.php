<?php

namespace Tests\Unit\Modules\Content;

use App\Modules\Content\Enums\SaveIntent;
use App\Modules\Content\Support\PostTermSynchronizer;
use Illuminate\Support\Facades\Validator;
use App\Modules\Content\Types\PageContentType;
use App\Modules\Content\Types\PostContentType;
use App\Modules\Content\Types\ContentTypeRegistry;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class ContentTypeRegistryTest extends TestCase
{
    private ContentTypeRegistry $registry;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registry = new ContentTypeRegistry([new PostContentType, new PageContentType]);
    }

    #[Test]
    public function it_resolves_a_type_from_its_route_key(): void
    {
        $this->assertInstanceOf(PostContentType::class, $this->registry->for('posts'));
        $this->assertInstanceOf(PageContentType::class, $this->registry->for('pages'));
    }

    #[Test]
    public function it_returns_404_for_an_unknown_type(): void
    {
        try {
            $this->registry->for('comments');
            $this->fail('Expected a 404 HttpException.');
        } catch (HttpException $e) {
            $this->assertSame(404, $e->getStatusCode());
        }
    }

    #[Test]
    public function it_lists_every_registered_type(): void
    {
        $this->assertCount(2, $this->registry->all());
    }

    #[Test]
    public function pages_do_not_expose_post_only_fields(): void
    {
        $page = $this->registry->for('pages');
        $post = $this->registry->for('posts');

        $this->assertNotContains('excerpt', $page->draftFields());
        $this->assertNotContains('cover_media_id', $page->draftFields());
        $this->assertContains('excerpt', $post->draftFields());
        $this->assertContains('cover_media_id', $post->draftFields());
        $this->assertTrue($post->supportsTerms());
        $this->assertFalse($page->supportsTerms());
    }

    #[Test]
    public function save_intent_rejects_unknown_values_with_422(): void
    {
        $this->assertSame(SaveIntent::Schedule, SaveIntent::fromRequest('schedule'));

        try {
            SaveIntent::fromRequest('delete');
            $this->fail('Expected a 422 HttpException.');
        } catch (HttpException $e) {
            $this->assertSame(422, $e->getStatusCode());
        }
    }

    #[Test]
    public function term_slugs_must_be_lowercase_hyphenated(): void
    {
        $rules = fn (string $slug) => Validator::make(['name' => 'Tag', 'slug' => $slug], PostTermSynchronizer::INPUT_RULES);

        $this->assertTrue($rules('good-slug-1')->passes());
        $this->assertTrue($rules('Bad_Slug')->fails());
        $this->assertTrue($rules('under_score')->fails());
    }
}
