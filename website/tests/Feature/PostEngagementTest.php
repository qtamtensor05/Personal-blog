<?php

declare(strict_types=1);

use App\Application\Content\UseCases\ManagePostInteractions\PublishLikeNotificationService;
use App\Infrastructure\Content\Jobs\ProcessLikeNotification;
use Illuminate\Auth\GenericUser;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

uses(DatabaseTransactions::class);

function engagementUser(string $label): GenericUser
{
    $id = DB::table('users')->insertGetId([
        'name' => $label,
        'email' => strtolower(str_replace(' ', '-', $label)).'-'.bin2hex(random_bytes(4)).'@example.test',
        'password' => 'not-used',
        'status' => 'ACTIVE',
        'email_verified_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return new GenericUser((array) DB::table('users')->where('id', $id)->first());
}

function engagementAvatar(int $userId, string $url): void
{
    $mediaId = DB::table('media_assets')->insertGetId([
        'scope' => 'USER',
        'uploaded_by' => $userId,
        'cloud_name' => 'test',
        'original_name' => 'avatar.png',
        'secure_url' => $url,
        'status' => 'READY',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    DB::table('users')->where('id', $userId)->update(['avatar_media_id' => $mediaId]);
}

beforeEach(function (): void {
    if (DB::getDriverName() !== 'mysql' || ! str_contains(DB::connection()->getDatabaseName(), '_test_')) {
        throw new RuntimeException('Engagement tests require isolated MySQL *_test_* database.');
    }
    $this->owner = engagementUser('Blog Owner');
    $this->reader = engagementUser('First Reader');
    $this->other = engagementUser('Second Reader');
    $this->slug = 'engagement-'.bin2hex(random_bytes(4));
    $this->blogId = DB::table('blogs')->insertGetId([
        'owner_user_id' => $this->owner->id,
        'name' => 'Engagement Blog',
        'slug' => $this->slug,
        'status' => 'PUBLISHED',
        'visibility' => 'PUBLIC',
        'discoverable' => true,
        'published_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $this->postId = DB::table('posts')->insertGetId([
        'blog_id' => $this->blogId,
        'title' => 'Engagement Post',
        'slug' => 'entry',
        'author_user_id' => $this->owner->id,
        'content_json' => json_encode(['type' => 'doc', 'content' => [['type' => 'paragraph']]]),
        'status' => 'PUBLISHED',
        'published_at' => now(),
        'updated_by' => $this->owner->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $this->postUrl = route('public.post', [$this->slug, 'entry']);
    $this->likeUrl = route('public.post.like', [$this->slug, 'entry']);
    $this->unlikeUrl = route('public.post.unlike', [$this->slug, 'entry']);
    $this->bookmarkUrl = route('public.post.bookmark', [$this->slug, 'entry']);
});

it('queues once for a new reader like and not for duplicate, bookmark, unlike or self like', function (): void {
    Queue::fake();
    $this->actingAs($this->reader)->put($this->likeUrl)->assertRedirect($this->postUrl);
    $this->put($this->likeUrl)->assertRedirect();
    $this->put($this->bookmarkUrl)->assertRedirect();
    $this->delete($this->unlikeUrl)->assertRedirect();
    $this->actingAs($this->owner)->put($this->likeUrl)->assertRedirect();

    Queue::assertPushed(ProcessLikeNotification::class, 1);
    expect(DB::table('notifications')->where('type', 'post.liked')->count())->toBe(0);
});

it('limits interaction writes per user across like and bookmark routes', function (): void {
    Queue::fake();
    $this->actingAs($this->reader);
    for ($attempt = 0; $attempt < 29; $attempt++) {
        $this->put($this->bookmarkUrl)->assertRedirect();
    }
    $this->put($this->likeUrl)->assertRedirect();
    $this->delete($this->unlikeUrl)->assertStatus(429);
    $this->actingAs($this->other)->put($this->likeUrl)->assertRedirect();
});

it('publishes one owner notification for retries and rapid unlike/re-like within one hour', function (): void {
    Queue::fake();
    $avatarUrl = 'https://example.test/avatars/first-reader.png';
    engagementAvatar((int) $this->reader->id, $avatarUrl);
    $this->actingAs($this->reader)->put($this->likeUrl)->assertRedirect();
    $job = new ProcessLikeNotification((int) $this->reader->id, $this->postId, (int) $this->owner->id, $this->slug, 'entry');
    $job->handle(app(PublishLikeNotificationService::class));
    $job->handle(app(PublishLikeNotificationService::class));
    $this->delete($this->unlikeUrl)->assertRedirect();
    $this->put($this->likeUrl)->assertRedirect();
    $job->handle(app(PublishLikeNotificationService::class));
    expect(DB::table('notifications')->where('type', 'post.liked')->where('recipient_user_id', $this->owner->id)->count())->toBe(1);
    DB::table('notifications')->where('type', 'post.liked')->update(['created_at' => '2026-10-21 13:41:00']);
    $this->actingAs($this->owner)->get(route('notifications.index'))->assertOk()
        ->assertSee('First Reader')->assertSee('Engagement Post')->assertSee($this->postUrl)
        ->assertSee('13:41 21/10/2026')
        ->assertSee('src="'.$avatarUrl.'"', false);
    $notification = DB::table('notifications')->where('type', 'post.liked')->first();
    expect(json_decode($notification->data, true)['liker_user_id'])->toBe((int) $this->reader->id);
    expect(DB::table('post_likes')->where('user_id', $this->reader->id)->where('post_id', $this->postId)->exists())->toBeTrue();
});

it('keeps notification cooldowns independent for different readers', function (): void {
    Queue::fake();
    $service = app(PublishLikeNotificationService::class);
    foreach ([$this->reader, $this->other] as $reader) {
        $this->actingAs($reader)->put($this->likeUrl)->assertRedirect();
        (new ProcessLikeNotification((int) $reader->id, $this->postId, (int) $this->owner->id, $this->slug, 'entry'))
            ->handle($service);
    }
    expect(DB::table('notifications')->where('type', 'post.liked')->where('recipient_user_id', $this->owner->id)->count())->toBe(2);
});

it('resolves changed blog and post slugs before publishing and when opening a stored notification', function (): void {
    Queue::fake();
    $this->actingAs($this->reader)->put($this->likeUrl)->assertRedirect();
    $job = new ProcessLikeNotification((int) $this->reader->id, $this->postId, (int) $this->owner->id, $this->slug, 'entry');

    DB::table('blogs')->where('id', $this->blogId)->update(['slug' => 'renamed-blog']);
    DB::table('posts')->where('id', $this->postId)->update(['slug' => 'renamed-post']);
    $job->handle(app(PublishLikeNotificationService::class));

    $notification = DB::table('notifications')->where('type', 'post.liked')->first();
    expect($notification)->not->toBeNull();
    $data = json_decode($notification->data, true);
    expect($data['blog_id'])->toBe($this->blogId)
        ->and($data['post_id'])->toBe($this->postId);

    DB::table('blogs')->where('id', $this->blogId)->update(['slug' => 'latest-blog']);
    DB::table('posts')->where('id', $this->postId)->update(['slug' => 'latest-post']);
    $latestUrl = route('public.post', ['latest-blog', 'latest-post']);
    $this->actingAs($this->owner)->get(route('notifications.index'))->assertOk()
        ->assertSee($latestUrl)->assertDontSee(route('public.post', ['renamed-blog', 'renamed-post']));
    $this->get($latestUrl)->assertOk();

    DB::table('posts')->where('id', $this->postId)->update(['status' => 'DRAFT']);
    $this->get(route('notifications.index'))->assertOk()->assertDontSee($latestUrl);
});

it('hides a stored like notification link when the post or blog is inaccessible', function (): void {
    Queue::fake();
    $this->actingAs($this->reader)->put($this->likeUrl)->assertRedirect();
    (new ProcessLikeNotification((int) $this->reader->id, $this->postId, (int) $this->owner->id))
        ->handle(app(PublishLikeNotificationService::class));

    $this->actingAs($this->owner)->get(route('notifications.index'))->assertOk()->assertSee($this->postUrl);
    DB::table('blogs')->where('id', $this->blogId)->update(['visibility' => 'PRIVATE']);
    $this->get(route('notifications.index'))->assertOk()->assertDontSee($this->postUrl);
    DB::table('blogs')->where('id', $this->blogId)->update(['visibility' => 'PUBLIC']);
    DB::table('posts')->where('id', $this->postId)->update(['deleted_at' => now()]);
    $this->get(route('notifications.index'))->assertOk()->assertDontSee($this->postUrl);
});

it('renders management engagement labels in the active English locale', function (): void {
    $previous = app()->getLocale();
    app()->setLocale('en');

    try {
        $this->actingAs($this->owner)->get(route('blogs.posts.index', $this->blogId))->assertOk()
            ->assertSee(__('post.likes_count'))
            ->assertSee(__('post.views_count'));
    } finally {
        app()->setLocale($previous);
    }
});

it('shows the liker initial for notifications created before actor details were stored', function (): void {
    DB::table('notifications')->insert([
        'id' => (string) Str::uuid(),
        'recipient_user_id' => $this->owner->id,
        'type' => 'post.liked',
        'data' => json_encode([
            'title' => 'Bài viết mới được thích',
            'message' => 'Binh đã thích bài viết "Engagement Post".',
            'blog_slug' => $this->slug,
            'post_slug' => 'entry',
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
        'read_at' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs($this->owner)->get(route('notifications.index'))->assertOk()
        ->assertSee('>B</span>', false)
        ->assertDontSee('>♥</span>', false);
});

it('drops stale queued likes when the like, post or blog becomes inaccessible', function (): void {
    Queue::fake();
    $job = new ProcessLikeNotification((int) $this->reader->id, $this->postId, (int) $this->owner->id, $this->slug, 'entry');
    $service = app(PublishLikeNotificationService::class);
    $this->actingAs($this->reader)->put($this->likeUrl)->assertRedirect();
    $this->delete($this->unlikeUrl)->assertRedirect();
    $job->handle($service);
    $this->put($this->likeUrl)->assertRedirect();
    DB::table('posts')->where('id', $this->postId)->update(['status' => 'DRAFT']);
    $job->handle($service);
    DB::table('posts')->where('id', $this->postId)->update(['status' => 'PUBLISHED']);
    DB::table('blogs')->where('id', $this->blogId)->update(['visibility' => 'PRIVATE']);
    $job->handle($service);
    DB::table('blogs')->where('id', $this->blogId)->update(['visibility' => 'PUBLIC', 'deleted_at' => now()]);
    $job->handle($service);
    expect(DB::table('notifications')->where('type', 'post.liked')->count())->toBe(0);
});

it('shows current like and view totals and limits liker names to the owner', function (): void {
    Queue::fake();
    $avatarUrl = 'https://example.test/avatars/first-reader.png';
    engagementAvatar((int) $this->reader->id, $avatarUrl);
    $this->actingAs($this->reader)->put($this->likeUrl)->assertRedirect();
    $this->actingAs($this->other)->put($this->likeUrl)->assertRedirect();
    DB::table('post_likes')->where('user_id', $this->reader->id)->where('post_id', $this->postId)
        ->update(['created_at' => '2026-10-21 13:41:00']);
    $this->get($this->postUrl)->assertOk();
    $this->get($this->postUrl)->assertOk();
    $this->actingAs($this->owner)->get($this->postUrl)->assertOk();
    $management = route('blogs.posts.index', $this->blogId);
    $likers = route('blogs.posts.likers', [$this->blogId, $this->postId]);
    $this->get($management)->assertOk()->assertSee(__('post.likes_count'))->assertSee(__('post.views_count'))
        ->assertSee(__('post.likers'))
        ->assertSee('data-likers-url="'.$likers.'"', false)
        ->assertSee('data-likers-dialog', false)
        ->assertViewHas('engagement', fn (array $values): bool => $values[$this->postId]->likes === 2 && $values[$this->postId]->views === 1);
    $this->get($this->postUrl)->assertOk()
        ->assertSee('data-likers-url="'.$likers.'"', false)
        ->assertSee('data-likers-count="2"', false)
        ->assertSeeInOrder(['data-likers-open', 'class="post-content"'], false)
        ->assertDontSee(__('post.likes_count'))
        ->assertDontSee('data-likers-post-title', false);
    $this->get($likers)->assertOk()->assertSee('First Reader')->assertSee('Second Reader')
        ->assertSee('data-likers-total="2"', false)
        ->assertSee('13:41')->assertSee('21/10/2026 13:41')
        ->assertSee('src="'.$avatarUrl.'"', false)->assertDontSee('Thêm bạn bè');
    $this->actingAs($this->reader)->get($management)->assertNotFound();
    $this->get($this->postUrl)->assertOk()
        ->assertSee('aria-label="'.__('post.likes_count').': 2"', false)
        ->assertDontSee('data-likers-open', false)
        ->assertDontSee('data-likers-dialog', false);
    $this->get($likers)->assertNotFound();
    $this->delete($this->unlikeUrl)->assertRedirect();
    $this->get($this->postUrl)->assertOk()->assertSee('aria-label="'.__('post.likes_count').': 1"', false);
    $this->actingAs($this->owner)->get($likers)->assertOk()->assertDontSee('First Reader')
        ->assertSee('data-likers-total="1"', false);
    expect(DB::table('post_views')->where('post_id', $this->postId)->count())->toBe(2)
        ->and(DB::table('post_likes')->where('post_id', $this->postId)->count())->toBe(1);
});

it('shows the public like total to guests without exposing the owner liker dialog', function (): void {
    DB::table('post_likes')->insert([
        'user_id' => $this->reader->id,
        'post_id' => $this->postId,
        'created_at' => now(),
    ]);

    $this->get($this->postUrl)->assertOk()
        ->assertSee('aria-label="'.__('post.likes_count').': 1"', false)
        ->assertDontSee('data-likers-open', false)
        ->assertDontSee('data-likers-dialog', false);
});

it('shows an empty liker list to the owner and redirects guests to login', function (): void {
    $likers = route('blogs.posts.likers', [$this->blogId, $this->postId]);
    $this->get($likers)->assertRedirect(route('login'));
    $this->actingAs($this->owner)->get($likers)->assertOk()->assertSee(__('post.no_likers'));
});

it('returns liker data as JSON only to the owner for safe dialog rendering', function (): void {
    Queue::fake();
    $name = '<img src=x onerror=alert(1)>';
    DB::table('users')->where('id', $this->reader->id)->update(['name' => $name]);
    $this->actingAs($this->reader)->put($this->likeUrl)->assertRedirect();

    $url = route('blogs.posts.likers', [$this->blogId, $this->postId]);
    $this->actingAs($this->owner)->getJson($url)->assertOk()
        ->assertJsonPath('total', 1)
        ->assertJsonPath('page', 1)
        ->assertJsonPath('likers.0.name', $name)
        ->assertJsonMissingPath('likers.0.profilePath')
        ->assertHeader('content-type', 'application/json');
    $this->actingAs($this->other)->getJson($url)->assertNotFound();
});

it('shows the publication clock before the date on the real post page', function (): void {
    DB::table('posts')->where('id', $this->postId)->update(['published_at' => '2026-10-21 13:41:00']);

    $this->get($this->postUrl)->assertOk()->assertSee('13:41 21/10/2026');
});

it('shows current views in the saved-post card for a permitted post', function (): void {
    $this->actingAs($this->reader)->put($this->bookmarkUrl)->assertRedirect();
    $this->get($this->postUrl)->assertOk();
    $this->get($this->postUrl)->assertOk();
    $this->actingAs($this->other)->get($this->postUrl)->assertOk();
    $this->actingAs($this->owner)->get($this->postUrl)->assertOk();

    $this->actingAs($this->reader)->get(route('bookmarks.index'))->assertOk()
        ->assertSee('images/illustrations/post-cover.svg')
        ->assertSee(__('post.views_count').': 2')
        ->assertViewHas('posts', fn (array $posts): bool => count($posts) === 1 && $posts[0]->engagement->views === 2);

    DB::table('posts')->where('id', $this->postId)->update(['status' => 'DRAFT']);
    $this->get(route('bookmarks.index'))->assertOk()->assertDontSee('Engagement Post');
});

it('shows a post cover and current engagement on catalog and saved cards', function (): void {
    Queue::fake();
    $coverUrl = 'https://example.test/covers/engagement-post.jpg';
    $coverId = DB::table('media_assets')->insertGetId([
        'scope' => 'BLOG',
        'blog_id' => $this->blogId,
        'uploaded_by' => $this->owner->id,
        'cloud_name' => 'test',
        'original_name' => 'cover.jpg',
        'secure_url' => $coverUrl,
        'status' => 'READY',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    DB::table('posts')->where('id', $this->postId)->update(['cover_media_id' => $coverId]);

    $this->actingAs($this->reader)->put($this->likeUrl)->assertRedirect();
    $this->put($this->bookmarkUrl)->assertRedirect();
    $this->get($this->postUrl)->assertOk();

    $this->get($this->postUrl)->assertOk()->assertSee('src="'.$coverUrl.'"', false);

    foreach ([route('posts.index'), route('bookmarks.index')] as $url) {
        $this->get($url)->assertOk()
            ->assertSee('src="'.$coverUrl.'"', false)
            ->assertSee('href="'.$this->postUrl.'" class="flex h-full flex-col', false)
            ->assertSee(__('post.likes_count').': 1')
            ->assertSee(__('post.views_count').': 1');
    }
});

it('shows the shared placeholder inside a post without a cover and in its editor', function (): void {
    $placeholder = 'images/illustrations/post-cover.svg';

    $this->get($this->postUrl)->assertOk()->assertSee($placeholder);
    $this->actingAs($this->owner)->get(route('blogs.posts.edit', [$this->blogId, $this->postId]))
        ->assertOk()->assertSee($placeholder)->assertSee('data-cover-image-input', false);
});

it('retires the public user profile route without affecting private profile editing', function (): void {
    $this->get('/users/'.$this->reader->id)->assertNotFound();
    $this->actingAs($this->reader)->get(route('profile.edit'))->assertOk();
    $this->get($this->postUrl)->assertOk()->assertDontSee('/users/'.$this->reader->id);
});

it('paginates the owner-only liker list at twenty names', function (): void {
    for ($number = 1; $number <= 21; $number++) {
        $reader = engagementUser('Pagination Reader '.$number);
        DB::table('post_likes')->insert([
            'user_id' => $reader->id,
            'post_id' => $this->postId,
            'created_at' => now(),
        ]);
    }
    $url = route('blogs.posts.likers', [$this->blogId, $this->postId]);
    $this->actingAs($this->owner)->get($url)->assertOk()
        ->assertSee('Pagination Reader 21')->assertDontSee('Pagination Reader 1<', false);
    $this->get($url.'?page=2')->assertOk()->assertSee('Pagination Reader 1');
});

it('counts each permitted account or guest session once per post across days, but no owner or hidden reads', function (): void {
    $first = $this->get($this->postUrl)->assertOk();
    foreach ($first->headers->getCookies() as $cookie) {
        if ($cookie->getName() === config('session.cookie')) {
            $this->withUnencryptedCookie($cookie->getName(), $cookie->getValue());
        }
    }
    $this->get($this->postUrl)->assertOk();
    expect(DB::table('post_views')->where('post_id', $this->postId)->count())->toBe(1);
    $this->actingAs($this->reader)->get($this->postUrl)->assertOk();
    $this->get($this->postUrl)->assertOk();
    $this->actingAs($this->owner)->get($this->postUrl)->assertOk();
    expect(DB::table('post_views')->where('post_id', $this->postId)->count())->toBe(2);
    $this->actingAs($this->reader)->travel(1)->days();
    $this->get($this->postUrl)->assertOk();
    expect(DB::table('post_views')->where('post_id', $this->postId)->count())->toBe(2);
    $guestView = DB::table('post_views')->where('post_id', $this->postId)->orderBy('created_at')->first();
    expect(DB::table('post_views')->insertOrIgnore([
        'post_id' => $this->postId,
        'viewer_key' => $guestView->viewer_key,
        'view_date' => now('UTC')->addDay()->toDateString(),
        'created_at' => now(),
    ]))->toBe(0);
    $this->actingAs($this->other)->get($this->postUrl)->assertOk();
    expect(DB::table('post_views')->where('post_id', $this->postId)->count())->toBe(3);
    DB::table('posts')->where('id', $this->postId)->update(['status' => 'DRAFT']);
    $this->get($this->postUrl)->assertNotFound();
    DB::table('posts')->where('id', $this->postId)->update(['status' => 'PUBLISHED']);
    DB::table('blogs')->where('id', $this->blogId)->update(['visibility' => 'PRIVATE']);
    $this->get($this->postUrl)->assertNotFound();
    DB::table('blogs')->where('id', $this->blogId)->update(['visibility' => 'PUBLIC']);
    DB::table('posts')->where('id', $this->postId)->update(['deleted_at' => now()]);
    $this->actingAs($this->reader)->get($this->postUrl)->assertNotFound();
    expect(DB::table('post_views')->where('post_id', $this->postId)->count())->toBe(3);
});
