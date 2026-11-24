<?php

use App\Application\Content\DTOs\PostRecord;
use App\Domain\Content\Enums\PostStatus;
use App\Domain\Content\ValueObjects\TiptapDocument;
use App\Infrastructure\Content\Persistence\QueryBuilderPublicPostCatalog;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    $this->previousConnection = DB::getDefaultConnection();
    config()->set('database.connections.cover_test', ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']);
    DB::setDefaultConnection('cover_test');
    DB::statement('CREATE TABLE users (id INTEGER PRIMARY KEY, name TEXT, deleted_at TEXT NULL)');
    DB::statement('CREATE TABLE blogs (id INTEGER PRIMARY KEY, slug TEXT, status TEXT, visibility TEXT, discoverable INTEGER, deleted_at TEXT NULL)');
    DB::statement('CREATE TABLE posts (id INTEGER PRIMARY KEY, blog_id INTEGER, author_user_id INTEGER, title TEXT, slug TEXT, excerpt TEXT NULL, content_json TEXT, cover_media_id INTEGER NULL, status TEXT, is_featured INTEGER, published_at TEXT, deleted_at TEXT NULL)');
    DB::statement('CREATE TABLE media_assets (id INTEGER PRIMARY KEY, blog_id INTEGER, scope TEXT, status TEXT, deleted_at TEXT NULL, secure_url TEXT)');
    DB::table('users')->insert(['id' => 1, 'name' => 'Author']);
    DB::table('blogs')->insert(['id' => 1, 'slug' => 'blog', 'status' => 'PUBLISHED', 'visibility' => 'PUBLIC', 'discoverable' => 1]);
    DB::table('media_assets')->insert([
        ['id' => 10, 'blog_id' => 1, 'scope' => 'BLOG', 'status' => 'READY', 'secure_url' => 'https://example.com/content.jpg'],
        ['id' => 11, 'blog_id' => 1, 'scope' => 'BLOG', 'status' => 'READY', 'secure_url' => 'https://example.com/cover.jpg'],
        ['id' => 12, 'blog_id' => 2, 'scope' => 'BLOG', 'status' => 'READY', 'secure_url' => 'https://example.com/other-blog.jpg'],
        ['id' => 13, 'blog_id' => 1, 'scope' => 'BLOG', 'status' => 'PENDING', 'secure_url' => 'https://example.com/not-ready.jpg'],
    ]);
});

afterEach(function (): void {
    DB::purge('cover_test');
    DB::setDefaultConnection($this->previousConnection);
});

it('uses an explicit cover or the first published content image across public catalogs', function (): void {
    foreach ([[1, 11, 10], [2, null, 10], [3, null, 12], [4, null, 13], [5, null, null], [6, 13, 10], [7, 999, 10]] as [$id, $cover, $image]) {
        $content = ['type' => 'doc', 'content' => $image === null ? [] : [
            ['type' => 'image', 'attrs' => ['mediaId' => $image]],
            ['type' => 'image', 'attrs' => ['mediaId' => 11]],
        ]];
        DB::table('posts')->insert(['id' => $id, 'blog_id' => 1, 'author_user_id' => 1, 'title' => 'Post '.$id, 'slug' => 'post-'.$id, 'content_json' => json_encode($content), 'cover_media_id' => $cover, 'status' => 'PUBLISHED', 'is_featured' => 1, 'published_at' => '2026-11-23']);
    }
    DB::table('media_assets')->where('id', 13)->update(['status' => 'READY', 'deleted_at' => '2026-11-23']);
    $catalog = new QueryBuilderPublicPostCatalog;
    foreach ([$catalog->paginate(null, 1, 9)->items, $catalog->recent(9), $catalog->featured(9)] as $items) {
        $urls = collect($items)->keyBy('id');
        expect($urls[1]->coverUrl)->toBe('https://example.com/cover.jpg')
            ->and($urls[2]->coverUrl)->toBe('https://example.com/content.jpg')
            ->and($urls[3]->coverUrl)->toBe('https://example.com/cover.jpg')
            ->and($urls[4]->coverUrl)->toBe('https://example.com/cover.jpg')
            ->and($urls[5]->coverUrl)->toBeNull()
            ->and($urls[6]->coverUrl)->toBe('https://example.com/content.jpg')
            ->and($urls[7]->coverUrl)->toBe('https://example.com/content.jpg');
    }
});

it('resolves a preview image without changing the selected cover or body', function (): void {
    $document = TiptapDocument::fromArray(['type' => 'doc', 'content' => [
        ['type' => 'image', 'attrs' => ['mediaId' => 10]],
        ['type' => 'image', 'attrs' => ['mediaId' => 11]],
    ]]);
    foreach ([null, 99] as $cover) {
        $post = new PostRecord(1, 1, 1, 'Post', 'post', null, $document, null, PostStatus::Published, $cover, null, null, null, false, '2026-11-23', '2026-11-23');
        expect($post->previewMediaId([10 => 'https://example.com/body.jpg', 99 => 'https://example.com/cover.jpg']))->toBe($cover ?? 10)->and($post->coverMediaId)->toBe($cover)->and($post->content->mediaIds())->toBe([10, 11]);
        expect($post->previewMediaId([11 => 'https://example.com/later.jpg']))->toBe(11)
            ->and($post->previewMediaId([]))->toBeNull();
    }
});

it('shows configured distinct covers without duplicating body images', function (bool $enabled, bool $available, int $bodyId, bool $visible): void {
    $document = TiptapDocument::fromArray(['type' => 'doc', 'content' => [['type' => 'image', 'attrs' => ['mediaId' => $bodyId]]]]);
    $html = view('public.partials.post-cover', [
        'layout' => (object) ['showCover' => $enabled],
        'cover' => $available ? ['url' => 'https://example.com/explicit.jpg', 'width' => 100, 'height' => 100] : null,
        'post' => (object) ['coverMediaId' => 99, 'content' => $document],
    ])->render();
    if ($visible) {
        expect($html)->toContain('https://example.com/explicit.jpg', 'data-image-frame');
    } else {
        expect($html)->not->toContain('<img');
    }
})->with([[true, true, 10, true], [false, true, 10, false], [true, true, 99, false], [true, false, 10, false]]);
