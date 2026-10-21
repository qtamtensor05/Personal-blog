<?php

declare(strict_types=1);

use Illuminate\Auth\GenericUser;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;

uses(DatabaseTransactions::class);

function interactionUser(string $suffix): GenericUser
{
    $id = (int) DB::table('users')->insertGetId([
        'name' => 'Reader '.$suffix,
        'email' => 'reader-'.$suffix.'@example.test',
        'email_verified_at' => now(),
        'password' => 'not-used-by-acting-as',
        'status' => 'ACTIVE',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return new GenericUser((array) DB::table('users')->where('id', $id)->first());
}

function interactionBlog(int $ownerId, string $slug, string $status = 'PUBLISHED', string $visibility = 'PUBLIC'): int
{
    return (int) DB::table('blogs')->insertGetId([
        'owner_user_id' => $ownerId,
        'name' => 'Blog '.$slug,
        'slug' => $slug,
        'status' => $status,
        'visibility' => $visibility,
        'discoverable' => true,
        'published_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

function interactionPost(int $blogId, int $authorId, string $slug, string $status = 'PUBLISHED'): int
{
    return (int) DB::table('posts')->insertGetId([
        'blog_id' => $blogId,
        'title' => 'Post '.$slug,
        'slug' => $slug,
        'author_user_id' => $authorId,
        'content_json' => json_encode(['type' => 'doc', 'content' => [['type' => 'paragraph']]]),
        'status' => $status,
        'published_at' => $status === 'PUBLISHED' ? now() : null,
        'updated_by' => $authorId,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

beforeEach(function (): void {
    $database = DB::connection()->getDatabaseName();
    if (DB::getDriverName() !== 'mysql' || ! str_contains($database, '_test_')) {
        throw new RuntimeException('Post interaction tests require an isolated MySQL *_test_* database.');
    }

    $suffix = bin2hex(random_bytes(5));
    $this->readerOne = interactionUser($suffix.'-one');
    $this->readerTwo = interactionUser($suffix.'-two');
    $this->blogSlug = 'reactions-'.$suffix;
    $this->postSlug = 'first-post';
    $this->blogId = interactionBlog((int) $this->readerOne->id, $this->blogSlug);
    $this->postId = interactionPost($this->blogId, (int) $this->readerOne->id, $this->postSlug);
});

it('lets guests read the real post but redirects interaction and saved-post requests to login', function (): void {
    $this->get(route('public.post', [$this->blogSlug, $this->postSlug]))
        ->assertOk()
        ->assertSee(__('post.sign_in_to_like'))
        ->assertSee(__('post.sign_in_to_bookmark'));

    $this->put(route('public.post.like', [$this->blogSlug, $this->postSlug]))->assertRedirect(route('login'));
    $this->put(route('public.post.bookmark', [$this->blogSlug, $this->postSlug]))->assertRedirect(route('login'));
    $this->get(route('bookmarks.index'))->assertRedirect(route('login'));

    expect(DB::table('post_likes')->count())->toBe(0)
        ->and(DB::table('post_bookmarks')->count())->toBe(0);
});

it('persists separate like and bookmark states for each reader and toggles them off', function (): void {
    $like = route('public.post.like', [$this->blogSlug, $this->postSlug]);
    $unlike = route('public.post.unlike', [$this->blogSlug, $this->postSlug]);
    $bookmark = route('public.post.bookmark', [$this->blogSlug, $this->postSlug]);
    $unbookmark = route('public.post.unbookmark', [$this->blogSlug, $this->postSlug]);
    $post = route('public.post', [$this->blogSlug, $this->postSlug]);

    $this->actingAs($this->readerOne)->put($like)->assertRedirect($post);
    $this->get($post)->assertOk()
        ->assertSee(__('post.unlike'))
        ->assertSee(__('post.bookmark'))
        ->assertSee('aria-pressed="true"', false)
        ->assertSee('aria-pressed="false"', false)
        ->assertSee('name="_token"', false);
    $this->put($bookmark)->assertRedirect($post);
    $this->get($post)->assertOk()
        ->assertSee(__('post.unlike'))
        ->assertSee(__('post.unbookmark'))
        ->assertDontSee('aria-pressed="false"', false);

    $this->actingAs($this->readerTwo)->get($post)->assertOk()
        ->assertSee(__('post.like'))->assertSee(__('post.bookmark'))
        ->assertDontSee('aria-pressed="true"', false);
    $this->put($bookmark)->assertRedirect($post);

    expect(DB::table('post_likes')->where('user_id', $this->readerOne->id)->count())->toBe(1)
        ->and(DB::table('post_likes')->where('user_id', $this->readerTwo->id)->count())->toBe(0)
        ->and(DB::table('post_bookmarks')->count())->toBe(2);

    $this->actingAs($this->readerOne)->delete($unlike)->assertRedirect($post);
    $this->delete($unbookmark)->assertRedirect($post);
    $this->get($post)->assertOk()->assertSee(__('post.like'))->assertSee(__('post.bookmark'));

    expect(DB::table('post_likes')->count())->toBe(0)
        ->and(DB::table('post_bookmarks')->where('user_id', $this->readerOne->id)->count())->toBe(0)
        ->and(DB::table('post_bookmarks')->where('user_id', $this->readerTwo->id)->count())->toBe(1);
});

it('treats repeated set and unset requests idempotently and enforces a unique user-post pair', function (): void {
    $this->actingAs($this->readerOne);
    $like = route('public.post.like', [$this->blogSlug, $this->postSlug]);
    $bookmark = route('public.post.bookmark', [$this->blogSlug, $this->postSlug]);

    $this->put($like)->assertRedirect();
    $this->put($like)->assertRedirect();
    $this->put($bookmark)->assertRedirect();
    $this->put($bookmark)->assertRedirect();
    expect(DB::table('post_likes')->count())->toBe(1)
        ->and(DB::table('post_bookmarks')->count())->toBe(1);

    expect(fn () => DB::table('post_likes')->insert([
        'user_id' => $this->readerOne->id,
        'post_id' => $this->postId,
        'created_at' => now(),
    ]))->toThrow(QueryException::class);

    $this->delete(route('public.post.unlike', [$this->blogSlug, $this->postSlug]))->assertRedirect();
    $this->delete(route('public.post.unlike', [$this->blogSlug, $this->postSlug]))->assertRedirect();
    $this->delete(route('public.post.unbookmark', [$this->blogSlug, $this->postSlug]))->assertRedirect();
    $this->delete(route('public.post.unbookmark', [$this->blogSlug, $this->postSlug]))->assertRedirect();
    expect(DB::table('post_likes')->count())->toBe(0)
        ->and(DB::table('post_bookmarks')->count())->toBe(0);
});

it('respects private and hidden blog visibility while keeping owner preview rules', function (): void {
    DB::table('blogs')->where('id', $this->blogId)->update(['visibility' => 'PRIVATE']);
    $post = route('public.post', [$this->blogSlug, $this->postSlug]);
    $like = route('public.post.like', [$this->blogSlug, $this->postSlug]);

    $this->actingAs($this->readerTwo)->get($post)->assertNotFound();
    $this->put($like)->assertNotFound();
    $this->actingAs($this->readerOne)->get($post)->assertOk();
    $this->put($like)->assertRedirect($post);

    DB::table('blogs')->where('id', $this->blogId)->update(['status' => 'HIDDEN']);
    $this->actingAs($this->readerTwo)->get($post)->assertNotFound();
    $this->put($like)->assertNotFound();
    $this->actingAs($this->readerOne)->get($post)->assertOk();
});

it('allows an eligible reader to interact with an unlisted, non-discoverable post by direct URL', function (): void {
    DB::table('blogs')->where('id', $this->blogId)->update([
        'visibility' => 'UNLISTED',
        'discoverable' => false,
    ]);

    $post = route('public.post', [$this->blogSlug, $this->postSlug]);
    $this->actingAs($this->readerTwo)->get($post)->assertOk();
    $this->put(route('public.post.bookmark', [$this->blogSlug, $this->postSlug]))
        ->assertRedirect($post);
    $this->get(route('bookmarks.index'))->assertOk()->assertSee('Post '.$this->postSlug);
});

it('rejects unpublished and deleted posts and omits them from bookmarks', function (): void {
    $this->actingAs($this->readerTwo);
    $post = route('public.post', [$this->blogSlug, $this->postSlug]);
    $bookmark = route('public.post.bookmark', [$this->blogSlug, $this->postSlug]);
    $this->put($bookmark)->assertRedirect($post);

    DB::table('posts')->where('id', $this->postId)->update(['status' => 'DRAFT']);
    $this->get($post)->assertNotFound();
    $this->put($bookmark)->assertNotFound();
    $this->get(route('bookmarks.index'))->assertOk()->assertDontSee('Post '.$this->postSlug);

    DB::table('posts')->where('id', $this->postId)->update(['status' => 'ARCHIVED']);
    $this->get($post)->assertNotFound();
    $this->put($bookmark)->assertNotFound();

    DB::table('posts')->where('id', $this->postId)->update(['status' => 'PUBLISHED', 'deleted_at' => now()]);
    $this->get($post)->assertNotFound();
    $this->put(route('public.post.like', [$this->blogSlug, $this->postSlug]))->assertNotFound();
    $this->get(route('bookmarks.index'))->assertOk()->assertDontSee('Post '.$this->postSlug);
});

it('shows only the viewer’s saved posts that remain visible to them', function (): void {
    $secondSlug = 'second-post';
    interactionPost($this->blogId, (int) $this->readerOne->id, $secondSlug);
    $firstBookmark = route('public.post.bookmark', [$this->blogSlug, $this->postSlug]);
    $secondBookmark = route('public.post.bookmark', [$this->blogSlug, $secondSlug]);

    $this->actingAs($this->readerOne)->put($firstBookmark)->assertRedirect();
    $this->actingAs($this->readerTwo)->put($secondBookmark)->assertRedirect();
    $this->actingAs($this->readerOne)->get(route('bookmarks.index'))->assertOk()
        ->assertSee('Post '.$this->postSlug)->assertDontSee('Post '.$secondSlug);
    $this->actingAs($this->readerTwo)->get(route('bookmarks.index'))->assertOk()
        ->assertSee('Post '.$secondSlug)->assertDontSee('Post '.$this->postSlug);

    DB::table('blogs')->where('id', $this->blogId)->update(['visibility' => 'PRIVATE']);
    $this->get(route('bookmarks.index'))->assertOk()->assertDontSee('Post '.$secondSlug);
    $this->actingAs($this->readerOne)->get(route('bookmarks.index'))->assertOk()
        ->assertSee('Post '.$this->postSlug);

    DB::table('blogs')->where('id', $this->blogId)->update(['deleted_at' => now()]);
    $this->get(route('bookmarks.index'))->assertOk()->assertDontSee('Post '.$this->postSlug);
});
