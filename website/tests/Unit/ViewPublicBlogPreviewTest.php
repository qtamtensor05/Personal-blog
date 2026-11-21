<?php

use App\Application\BlogDesign\Contracts\BlogDesignRepository;
use App\Application\BlogDesign\DTOs\BlogDesignRecord;
use App\Application\Blogs\Contracts\BlogRepository;
use App\Application\Blogs\Contracts\BlogSettingsRepository;
use App\Application\Blogs\DTOs\BlogRecord;
use App\Application\Blogs\Exceptions\BlogNotFound;
use App\Application\Blogs\UseCases\ViewPublicBlog\ViewPublicBlogService;
use App\Application\Content\Contracts\PageRepository;
use App\Application\Content\Contracts\PostRepository;
use App\Application\Content\Contracts\TaxonomyRepository;
use App\Domain\BlogDesign\ValueObjects\BlogDesignData;
use App\Domain\Blogs\Enums\BlogStatus;
use App\Domain\Blogs\Enums\BlogVisibility;
use App\Domain\Templates\TemplateStarters;

function previewBlogService(bool $changed, bool $private = false, bool $publishedDesign = true): ViewPublicBlogService
{
    $blog = new BlogRecord(1, 7, 'Blog', 'blog', null, BlogStatus::Published,
        $private ? BlogVisibility::Private : BlogVisibility::Public, true, '2026-11-21', null);
    $live = BlogDesignData::fromTemplate(1, 1, TemplateStarters::blog());
    $draft = $changed ? $live->withSlotContent(['hero' => ['title' => 'Unpublished title']]) : clone $live;
    $blogs = Mockery::mock(BlogRepository::class);
    $blogs->shouldReceive('findBySlug')->with('blog')->andReturn($blog);
    $settings = Mockery::mock(BlogSettingsRepository::class);
    $settings->shouldReceive('find')->andReturn(null);
    $designs = Mockery::mock(BlogDesignRepository::class);
    $designs->shouldReceive('find')->andReturn(new BlogDesignRecord(1, 1, 1, $publishedDesign ? $live : null, $draft, null));
    $posts = Mockery::mock(PostRepository::class);
    $posts->shouldReceive('publishedForBlog')->andReturn([]);
    $pages = Mockery::mock(PageRepository::class);
    $pages->shouldReceive('publishedForBlog')->andReturn([]);
    $taxonomy = Mockery::mock(TaxonomyRepository::class);
    $taxonomy->shouldReceive('categories')->andReturn([]);

    return new ViewPublicBlogService($blogs, $settings, $designs, $posts, $pages, $taxonomy);
}

it('does not label a published blog as draft when both snapshots are equal', function (): void {
    $page = previewBlogService(false)->execute('blog', 7);
    expect($page->isOwnerPreview)->toBeFalse()->and($page->noIndex)->toBeFalse();
});

it('warns the owner when the displayed design has unpublished changes', function (): void {
    $page = previewBlogService(true)->execute('blog', 7);
    expect($page->isOwnerPreview)->toBeTrue()->and($page->noIndex)->toBeTrue()
        ->and($page->slotContent['hero']['title'])->toBe('Unpublished title');
});

it('keeps unpublished design changes hidden from guests', function (): void {
    $page = previewBlogService(true)->execute('blog', null);
    expect($page->isOwnerPreview)->toBeFalse()->and($page->slotContent)->toBe([]);
});

it('warns owners when no design has been published or the blog is private', function (): void {
    expect(previewBlogService(false, false, false)->execute('blog', 7)->isOwnerPreview)->toBeTrue()
        ->and(previewBlogService(false, true)->execute('blog', 7)->isOwnerPreview)->toBeTrue();
});

it('still denies private blogs to guests', function (): void {
    previewBlogService(false, true)->execute('blog', null);
})->throws(BlogNotFound::class);
