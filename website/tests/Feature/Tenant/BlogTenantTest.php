<?php

/**
 * TASK-02: Blog / tenant – Feature tests for 2.1, 2.2, 2.3
 *
 * Covers:
 *  - Two-owner isolation: create, list, detail, Studio blog selector,
 *    settings changes, and forged blog/content/media IDs.
 *  - PUBLIC / UNLISTED / PRIVATE visibility in discovery and direct URLs.
 *  - HIDDEN / SUSPENDED / ARCHIVED / soft-deleted blog access rules.
 *  - Discoverability flag behaviour on the /blogs discovery route.
 *  - Slug editing, normalization, uniqueness collision → form error.
 *  - Additional settings fields (locale, timezone, social_links, discoverable).
 *  - Platform-controlled limits (storage_quota_bytes, post_limit) not owner-editable.
 *  - Create Blog form: slug and description preserved on validation error.
 */

use App\Filament\Blog\Pages\Studio;
use App\Models\User;
use App\Modules\Blogs\Actions\CreateBlogAction;
use App\Modules\Blogs\Actions\SaveBlogSettingsAction;
use App\Modules\Blogs\Queries\BlogSettingsQuery;
use App\Modules\Content\Actions\ArchiveContentAction;
use App\Modules\Content\Actions\SaveContentAction;
use App\Modules\Media\Actions\DeleteMediaAction;
use App\Modules\Media\Actions\UpdateMediaAction;
use Filament\Facades\Filament;
use Illuminate\Database\RecordNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function tenantActiveUser(string $suffix = ''): User
{
    return User::factory()->create([
        'status' => 'ACTIVE',
        'email_verified_at' => now(),
        'email' => 'tenant-'.uniqid().$suffix.'@example.test',
    ]);
}

function tenantBlog(
    User $owner,
    string $slug,
    string $status = 'PUBLISHED',
    string $visibility = 'PUBLIC',
    bool $discoverable = true,
): int {
    return DB::table('blogs')->insertGetId([
        'owner_user_id' => $owner->id,
        'name' => strtoupper($slug),
        'slug' => $slug,
        'status' => $status,
        'visibility' => $visibility,
        'discoverable' => $discoverable,
        'published_at' => $status === 'PUBLISHED' ? now() : null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

function tenantSettings(int $blogId): void
{
    if (! DB::table('blog_settings')->where('blog_id', $blogId)->exists()) {
        DB::table('blog_settings')->insert([
            'blog_id' => $blogId,
            'locale' => 'vi',
            'timezone' => 'Asia/Ho_Chi_Minh',
            'posts_per_page' => 10,
            'storage_quota_bytes' => 104857600,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}

function tenantPost(
    int $blogId,
    User $author,
    string $slug = 'post-a',
    string $status = 'PUBLISHED',
): int {
    return DB::table('posts')->insertGetId([
        'blog_id' => $blogId,
        'title' => strtoupper($slug),
        'slug' => $slug,
        'author_user_id' => $author->id,
        'updated_by' => $author->id,
        'content_json' => json_encode(['type' => 'doc', 'content' => []]),
        'status' => $status,
        'published_at' => $status === 'PUBLISHED' ? now() : null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

function tenantPublishedTemplate(User $user, string $slug = 'tenant-tmpl'): int
{
    return DB::table('templates')->insertGetId([
        'name' => 'Mẫu chuẩn',
        'slug' => $slug.'-'.uniqid(),
        'status' => 'PUBLISHED',
        'created_by' => $user->id,
        'updated_by' => $user->id,
        'published_by' => $user->id,
        'published_at' => now(),
        'version_number' => 1,
        'template_data' => json_encode([
            'schemaVersion' => 1,
            'project' => ['pages' => [['id' => 'home', 'sections' => [['type' => 'hero', 'content' => 'Xin chào'], ['type' => 'post-list']]]]],
            'themeDefaults' => ['accent' => '#147d73', 'layout' => 'journal'],
        ]),
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

// ---------------------------------------------------------------------------
// 2.1 – Two-owner tenant isolation
// ---------------------------------------------------------------------------

it('owner B cannot view or edit the Studio for a blog owned by owner A', function () {
    $a = tenantActiveUser('a');
    $b = tenantActiveUser('b');
    $blogA = app(CreateBlogAction::class)->execute($a, ['name' => 'Blog A', 'slug' => 'blog-a-iso']);

    $this->actingAs($b);
    Filament::setCurrentPanel(Filament::getPanel('blog'));

    Livewire::test(Studio::class, ['blogId' => $blogA])
        ->assertNotFound();
});

it('owner A list does not contain blogs owned by owner B', function () {
    $a = tenantActiveUser('a2');
    $b = tenantActiveUser('b2');
    $slugA = 'iso-blog-a-'.uniqid();
    $slugB = 'iso-blog-b-'.uniqid();

    tenantBlog($a, $slugA);
    tenantBlog($b, $slugB);

    $this->actingAs($a)->get('/workspace')
        ->assertOk()
        ->assertSee('iso-blog-a')
        ->assertDontSee('iso-blog-b');
});

it('owner B cannot read a DRAFT post that belongs to owner A blog', function () {
    $a = tenantActiveUser('a3');
    $b = tenantActiveUser('b3');
    $blogA = app(CreateBlogAction::class)->execute(
        $a,
        ['name' => 'Blog A Draft', 'slug' => 'blog-a-draft-'.uniqid()],
    );
    $post = app(SaveContentAction::class)->execute(
        $a,
        $blogA,
        'posts',
        null,
        [
            'title' => 'Secret draft',
            'slug' => 'secret-draft',
            'content_json' => ['type' => 'doc', 'content' => [['type' => 'paragraph']]],
            'category_ids' => [],
            'tag_ids' => [],
        ],
        null,
        'draft',
    );

    $this->actingAs($b)
        ->get("/workspace/blogs/{$blogA}/preview/posts/{$post}")
        ->assertNotFound();
});

it('owner B cannot save settings on a blog owned by owner A', function () {
    $a = tenantActiveUser('a4');
    $b = tenantActiveUser('b4');
    $blogA = app(CreateBlogAction::class)->execute(
        $a,
        ['name' => 'Owned by A', 'slug' => 'owned-by-a-'.uniqid()],
    );

    expect(fn () => app(SaveBlogSettingsAction::class)->execute($b, $blogA, [
        'name' => 'Hijacked',
        'slug' => 'hijacked-slug',
        'visibility' => 'PUBLIC',
        'posts_per_page' => 10,
    ]))->toThrow(HttpException::class);

    $this->assertDatabaseHas('blogs', ['id' => $blogA, 'name' => 'Owned by A']);
});

it('owner B cannot forge a media ID belonging to owner A blog', function () {
    $a = tenantActiveUser('a5');
    $b = tenantActiveUser('b5');
    $blogA = app(CreateBlogAction::class)->execute(
        $a,
        ['name' => 'Media A', 'slug' => 'media-a-'.uniqid()],
    );
    tenantSettings($blogA);

    $mediaId = DB::table('media_assets')->insertGetId([
        'blog_id' => $blogA,
        'uploaded_by' => $a->id,
        'cloud_name' => 'test-cloud',
        'scope' => 'BLOG',
        'status' => 'READY',
        'original_name' => 'test.jpg',
        'secure_url' => 'https://example.com/test.jpg',
        'cloudinary_asset_id' => 'fake-asset-id-'.uniqid(),
        'cloudinary_public_id' => 'fake/public-id',
        'bytes' => 1024,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $blogB = app(CreateBlogAction::class)->execute(
        $b,
        ['name' => 'Media B', 'slug' => 'media-b-'.uniqid()],
    );
    tenantSettings($blogB);

    expect(fn () => app(SaveBlogSettingsAction::class)->execute($b, $blogB, [
        'name' => 'Media B',
        'slug' => DB::table('blogs')->where('id', $blogB)->value('slug'),
        'visibility' => 'PUBLIC',
        'posts_per_page' => 10,
        'logo_media_id' => $mediaId,
    ]))->toThrow(ValidationException::class);
});

it('owner B cannot update or delete media belonging to owner A blog', function () {
    $a = tenantActiveUser('med-upd-a');
    $b = tenantActiveUser('med-upd-b');
    $blogA = app(CreateBlogAction::class)->execute($a, ['name' => 'Media A', 'slug' => 'med-upd-a-'.uniqid()]);
    $blogB = app(CreateBlogAction::class)->execute($b, ['name' => 'Media B', 'slug' => 'med-upd-b-'.uniqid()]);

    $mediaId = DB::table('media_assets')->insertGetId([
        'blog_id' => $blogA,
        'uploaded_by' => $a->id,
        'cloud_name' => 'test-cloud',
        'scope' => 'BLOG',
        'status' => 'READY',
        'original_name' => 'test.jpg',
        'secure_url' => 'https://example.com/test.jpg',
        'cloudinary_asset_id' => 'fake-asset-id-'.uniqid(),
        'cloudinary_public_id' => 'fake/public-id',
        'bytes' => 1024,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Owner B targeting Owner A's media under Blog B context must fail 404
    expect(fn () => app(UpdateMediaAction::class)->execute(
        $b, $blogB, $mediaId, ['alt_text' => 'hacked'],
    ))->toThrow(HttpException::class);

    // Owner B directly targeting Blog A must fail 404
    expect(fn () => app(UpdateMediaAction::class)->execute(
        $b, $blogA, $mediaId, ['alt_text' => 'hacked'],
    ))->toThrow(HttpException::class);

    // Owner B trying to delete Owner A's media under Blog B context must fail 404
    expect(fn () => app(DeleteMediaAction::class)->execute(
        $b, $blogB, $mediaId,
    ))->toThrow(RecordNotFoundException::class);
});

it('owner B cannot attach owner A media to a post cover or content JSON', function () {
    $a = tenantActiveUser('post-med-a');
    $b = tenantActiveUser('post-med-b');
    $blogA = app(CreateBlogAction::class)->execute($a, ['name' => 'Blog A', 'slug' => 'post-med-a-'.uniqid()]);
    $blogB = app(CreateBlogAction::class)->execute($b, ['name' => 'Blog B', 'slug' => 'post-med-b-'.uniqid()]);

    $mediaId = DB::table('media_assets')->insertGetId([
        'blog_id' => $blogA,
        'uploaded_by' => $a->id,
        'cloud_name' => 'test-cloud',
        'scope' => 'BLOG',
        'status' => 'READY',
        'original_name' => 'test.jpg',
        'secure_url' => 'https://example.com/test.jpg',
        'cloudinary_asset_id' => 'fake-asset-id-'.uniqid(),
        'cloudinary_public_id' => 'fake/public-id',
        'bytes' => 1024,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Cover media ID forged
    expect(fn () => app(SaveContentAction::class)->execute(
        $b, $blogB, 'posts', null, [
            'title' => 'Forged Cover',
            'slug' => 'forged-cover',
            'content_json' => ['type' => 'doc', 'content' => [['type' => 'paragraph']]],
            'cover_media_id' => $mediaId,
            'category_ids' => [],
            'tag_ids' => [],
        ], null, 'draft',
    ))->toThrow(ValidationException::class);

    // Content JSON embedded mediaId forged
    expect(fn () => app(SaveContentAction::class)->execute(
        $b, $blogB, 'posts', null, [
            'title' => 'Forged Content Media',
            'slug' => 'forged-content-media',
            'content_json' => ['type' => 'doc', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'hi']]], ['type' => 'image', 'attrs' => ['mediaId' => $mediaId]]]],
            'category_ids' => [],
            'tag_ids' => [],
        ], null, 'draft',
    ))->toThrow(ValidationException::class);
});

it('owner B cannot edit or archive a post belonging to owner A blog', function () {
    $a = tenantActiveUser('post-iso-a');
    $b = tenantActiveUser('post-iso-b');
    $blogA = app(CreateBlogAction::class)->execute($a, ['name' => 'Blog A', 'slug' => 'post-iso-a-'.uniqid()]);
    $blogB = app(CreateBlogAction::class)->execute($b, ['name' => 'Blog B', 'slug' => 'post-iso-b-'.uniqid()]);
    $postId = tenantPost($blogA, $a, 'post-a-iso');

    // Owner B cannot edit Post A under Blog B context
    expect(fn () => app(SaveContentAction::class)->execute(
        $b, $blogB, 'posts', $postId, [
            'title' => 'Hijacked Post',
            'slug' => 'hijacked-post',
            'content_json' => ['type' => 'doc', 'content' => [['type' => 'paragraph']]],
            'category_ids' => [],
            'tag_ids' => [],
        ], null, 'draft',
    ))->toThrow(HttpException::class);

    // Owner B cannot archive Post A
    expect(fn () => app(ArchiveContentAction::class)->execute(
        $b, $blogB, 'posts', $postId,
    ))->toThrow(RecordNotFoundException::class);
});

it('owner B cannot view protected design or page draft previews of owner A blog', function () {
    $a = tenantActiveUser('prev-iso-a');
    $b = tenantActiveUser('prev-iso-b');
    $blogA = app(CreateBlogAction::class)->execute($a, ['name' => 'Prev A', 'slug' => 'prev-iso-a-'.uniqid()]);

    $pageId = DB::table('pages')->insertGetId([
        'blog_id' => $blogA,
        'title' => 'About Page Draft',
        'slug' => 'about',
        'page_key' => 'about',
        'created_by' => $a->id,
        'updated_by' => $a->id,
        'content_json' => json_encode(['type' => 'doc', 'content' => []]),
        'status' => 'DRAFT',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs($b)->get("/workspace/blogs/{$blogA}/preview-design")->assertNotFound();
    $this->actingAs($b)->get("/workspace/blogs/{$blogA}/preview/pages/{$pageId}")->assertNotFound();
});

// ---------------------------------------------------------------------------
// 2.3 – PUBLIC / UNLISTED / PRIVATE visibility and discovery
// ---------------------------------------------------------------------------

it('a PUBLIC discoverable PUBLISHED blog appears in discovery', function () {
    $owner = tenantActiveUser('disc1');
    $slug = 'pub-disc-'.uniqid();
    tenantBlog($owner, $slug, 'PUBLISHED', 'PUBLIC', true);

    $this->get('/blogs?q='.urlencode($slug))
        ->assertOk()
        ->assertSee('/b/'.$slug);
});

it('a PUBLIC non-discoverable PUBLISHED blog does NOT appear in discovery', function () {
    $owner = tenantActiveUser('disc2');
    $slug = 'pub-nodiscoverable-'.uniqid();
    tenantBlog($owner, $slug, 'PUBLISHED', 'PUBLIC', false);

    $this->get('/blogs')
        ->assertOk()
        ->assertDontSee('/b/'.$slug);

    $this->get('/blogs?q='.urlencode($slug))
        ->assertOk()
        ->assertDontSee('/b/'.$slug);
});

it('an UNLISTED PUBLISHED blog does not appear in discovery but is reachable by direct URL', function () {
    $owner = tenantActiveUser('unlisted1');
    $slug = 'unlisted-blog-'.uniqid();
    tenantBlog($owner, $slug, 'PUBLISHED', 'UNLISTED', true);

    $this->get('/blogs')
        ->assertOk()
        ->assertDontSee('/b/'.$slug);

    $this->get('/blogs?q='.urlencode($slug))
        ->assertOk()
        ->assertDontSee('/b/'.$slug);

    $this->get('/b/'.$slug)->assertOk();
});

it('a PRIVATE PUBLISHED blog is not reachable by an anonymous user', function () {
    $owner = tenantActiveUser('priv1');
    $slug = 'private-blog-'.uniqid();
    tenantBlog($owner, $slug, 'PUBLISHED', 'PRIVATE', false);

    $this->get('/b/'.$slug)->assertNotFound();
});

it('a PRIVATE PUBLISHED blog is not reachable by another authenticated user', function () {
    $owner = tenantActiveUser('priv2');
    $other = tenantActiveUser('priv2b');
    $slug = 'private-blog-other-'.uniqid();
    tenantBlog($owner, $slug, 'PUBLISHED', 'PRIVATE', false);

    $this->actingAs($other)->get('/b/'.$slug)->assertNotFound();
});

it('a PRIVATE PUBLISHED blog is reachable by the owner', function () {
    $owner = tenantActiveUser('priv3');
    $slug = 'private-blog-owner-'.uniqid();
    tenantBlog($owner, $slug, 'PUBLISHED', 'PRIVATE', false);

    $this->actingAs($owner)->get('/b/'.$slug)->assertOk();
});

it('a HIDDEN blog does NOT appear in discovery', function () {
    $owner = tenantActiveUser('hidden1');
    $slug = 'hidden-blog-'.uniqid();
    DB::table('blogs')->insertGetId([
        'owner_user_id' => $owner->id,
        'name' => strtoupper($slug),
        'slug' => $slug,
        'status' => 'HIDDEN',
        'visibility' => 'PUBLIC',
        'discoverable' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->get('/blogs')
        ->assertOk()
        ->assertDontSee('/b/'.$slug);

    $this->get('/blogs?q='.urlencode($slug))
        ->assertOk()
        ->assertDontSee('/b/'.$slug);
});

it('a HIDDEN blog is not accessible via its direct public URL', function () {
    $owner = tenantActiveUser('hidden2');
    $slug = 'hidden-direct-'.uniqid();
    DB::table('blogs')->insertGetId([
        'owner_user_id' => $owner->id,
        'name' => strtoupper($slug),
        'slug' => $slug,
        'status' => 'HIDDEN',
        'visibility' => 'PUBLIC',
        'discoverable' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->get('/b/'.$slug)->assertNotFound();
});

it('a SUSPENDED blog is not publicly readable', function () {
    $owner = tenantActiveUser('suspended1');
    $slug = 'susp-blog-'.uniqid();
    tenantBlog($owner, $slug, 'SUSPENDED', 'PUBLIC', true);

    $this->get('/b/'.$slug)->assertNotFound();
});

it('a DRAFT blog is not accessible via its public URL', function () {
    $owner = tenantActiveUser('draft1');
    $slug = 'draft-blog-'.uniqid();
    tenantBlog($owner, $slug, 'DRAFT', 'PUBLIC', true);

    $this->get('/b/'.$slug)->assertNotFound();
});

it('a soft-deleted blog is not accessible via discovery or direct URL', function () {
    $owner = tenantActiveUser('del1');
    $slug = 'deleted-blog-'.uniqid();
    $id = tenantBlog($owner, $slug, 'PUBLISHED', 'PUBLIC', true);
    DB::table('blogs')->where('id', $id)->update(['deleted_at' => now()]);

    $this->get('/blogs')
        ->assertOk()
        ->assertDontSee('/b/'.$slug);

    $this->get('/blogs?q='.urlencode($slug))
        ->assertOk()
        ->assertDontSee('/b/'.$slug);

    $this->get('/b/'.$slug)->assertNotFound();
});

it('a published post in an UNLISTED blog is not reachable via another blog slug', function () {
    $owner = tenantActiveUser('unlistpost');
    $slug = 'unlisted-post-'.uniqid();
    $blogId = tenantBlog($owner, $slug, 'PUBLISHED', 'UNLISTED');
    tenantPost($blogId, $owner, 'my-post');

    $this->get('/b/'.$slug.'/posts/my-post')->assertOk();

    $otherSlug = 'other-'.uniqid();
    tenantBlog($owner, $otherSlug, 'PUBLISHED', 'PUBLIC');
    $this->get('/b/'.$otherSlug.'/posts/my-post')->assertNotFound();
});

// ---------------------------------------------------------------------------
// 2.2 – Blog settings: slug, additional fields, platform limits
// ---------------------------------------------------------------------------

it('rejects expanded slugs before creating or updating a blog', function () {
    $owner = tenantActiveUser();
    $expanded = str_repeat('ß', 81);
    expect(Str::length($expanded))->toBeLessThan(160);
    expect(Str::length(Str::slug($expanded)))->toBeGreaterThan(160);

    foreach ([['name' => 'Long slug', 'slug' => $expanded], ['name' => $expanded]] as $input) {
        try {
            app(CreateBlogAction::class)->execute($owner, $input);
            $this->fail('Expected slug validation error');
        } catch (ValidationException $e) {
            expect($e->errors())->toHaveKey('slug');
        }
    }
    expect(DB::table('blogs')->where('owner_user_id', $owner->id)->count())->toBe(0);
    $blog = app(CreateBlogAction::class)->execute($owner, ['name' => 'Boundary', 'slug' => str_repeat('ß', 80)]);
    $original = app(BlogSettingsQuery::class)->find($owner, $blog);
    try {
        app(SaveBlogSettingsAction::class)->execute($owner, $blog, array_replace($original, ['slug' => $expanded, 'name' => 'Must not save']));
        $this->fail('Expected slug validation error');
    } catch (ValidationException $e) {
        expect($e->errors())->toHaveKey('slug');
    }
    expect(app(BlogSettingsQuery::class)->find($owner, $blog))->toBe($original);
    app(SaveBlogSettingsAction::class)->execute($owner, $blog, array_replace($original, ['slug' => str_repeat('ß', 80)]));
    $this->assertDatabaseHas('blogs', ['id' => $blog, 'slug' => str_repeat('ss', 80)]);
});

it('merges stale Studio settings without restoring unchanged values and refreshes the snapshot', function () {
    $owner = tenantActiveUser();
    $blog = app(CreateBlogAction::class)->execute($owner, ['name' => 'Concurrent settings', 'slug' => 'original-'.uniqid()]);
    $this->actingAs($owner);
    Filament::setCurrentPanel(Filament::getPanel('blog'));
    $first = Livewire::test(Studio::class, ['blogId' => $blog, 'section' => 'settings']);
    $second = Livewire::test(Studio::class, ['blogId' => $blog, 'section' => 'settings']);
    $newSlug = 'updated-'.uniqid();
    $first->set('form.slug', $newSlug)
        ->set('form.seo_title', 'Latest SEO')
        ->set('form.social_links.twitter', 'https://example.com/new')
        ->call('saveSettings')->assertHasNoErrors();
    $second->set('form.description', 'Second session edit')
        ->set('form.social_links.github', 'https://github.com/example')
        ->call('saveSettings')->assertHasNoErrors()
        ->assertSet('form.slug', $newSlug)
        ->assertSet('originalSettings.slug', $newSlug);
    $stored = app(BlogSettingsQuery::class)->find($owner, $blog);
    expect($stored['slug'])->toBe($newSlug);
    expect($stored['seo_title'])->toBe('Latest SEO');
    expect($stored['description'])->toBe('Second session edit');
    expect($stored['social_links'])->toEqual([
        'twitter' => 'https://example.com/new', 'github' => 'https://github.com/example',
    ]);
    $second->set('form.social_links.twitter', '')->set('form.description', '')
        ->call('saveSettings')->assertHasNoErrors();
    $stored = app(BlogSettingsQuery::class)->find($owner, $blog);
    expect($stored['social_links'])->toBe(['github' => 'https://github.com/example']);
    expect($stored['description'])->toBe('');
});

it('preserves omitted social links and merges explicit edits with or without a snapshot', function (?bool $snapshot) {
    $owner = tenantActiveUser();
    $blog = app(CreateBlogAction::class)->execute($owner, ['name' => 'Links', 'slug' => 'links-'.uniqid()]);
    $base = app(BlogSettingsQuery::class)->find($owner, $blog);
    app(SaveBlogSettingsAction::class)->execute($owner, $blog, array_replace($base, [
        'social_links' => ['twitter' => 'https://example.com/twitter', 'github' => 'https://github.com/example'],
    ]));
    $base = app(BlogSettingsQuery::class)->find($owner, $blog);
    $original = $snapshot ? $base : null;
    unset($base['social_links']);
    foreach ([[], ['social_links' => []], ['social_links' => null]] as $input) {
        app(SaveBlogSettingsAction::class)->execute($owner, $blog, array_replace($base, $input), $original);
        expect(app(BlogSettingsQuery::class)->find($owner, $blog)['social_links'])->toHaveCount(2);
    }
    app(SaveBlogSettingsAction::class)->execute($owner, $blog, array_replace($base, [
        'social_links' => ['twitter' => '', 'facebook' => 'https://example.com/facebook'],
    ]), $original);
    expect(app(BlogSettingsQuery::class)->find($owner, $blog)['social_links'])->toEqual([
        'github' => 'https://github.com/example', 'facebook' => 'https://example.com/facebook',
    ]);
})->with([false, true]);

it('rejects null empty and unsupported locale and timezone settings', function (string $field, ?string $value) {
    $owner = tenantActiveUser();
    $blog = app(CreateBlogAction::class)->execute($owner, ['name' => 'Options', 'slug' => 'options-'.uniqid()]);
    $base = app(BlogSettingsQuery::class)->find($owner, $blog);
    try {
        app(SaveBlogSettingsAction::class)->execute($owner, $blog, array_replace($base, [$field => $value]));
        $this->fail('Expected field validation error');
    } catch (ValidationException $e) {
        expect($e->errors())->toHaveKey($field);
    }
    expect(app(BlogSettingsQuery::class)->find($owner, $blog))->toBe($base);
})->with([['locale', null], ['locale', ''], ['locale', 'fr'], ['timezone', null], ['timezone', ''], ['timezone', 'Mars/Olympus']]);

it('renders configured locale and safe social links on every public blog view', function () {
    $owner = tenantActiveUser();
    $slug = 'public-settings-'.uniqid();
    $blog = tenantBlog($owner, $slug);
    tenantSettings($blog);
    tenantPost($blog, $owner, 'sample');
    DB::table('pages')->insert([
        'blog_id' => $blog, 'title' => 'Sample', 'slug' => 'sample',
        'created_by' => $owner->id, 'updated_by' => $owner->id,
        'page_key' => 'sample', 'status' => 'PUBLISHED', 'content_json' => '{"type":"doc","content":[]}',
        'created_at' => now(), 'updated_at' => now(),
    ]);
    $settings = app(BlogSettingsQuery::class)->find($owner, $blog);
    app(SaveBlogSettingsAction::class)->execute($owner, $blog, array_replace($settings, [
        'locale' => 'en', 'timezone' => 'UTC',
        'social_links' => ['github' => 'https://github.com/example', 'twitter' => 'javascript:alert(1)'],
    ]));
    foreach (["/b/$slug", "/b/$slug/posts/sample", "/b/$slug/pages/sample"] as $url) {
        $this->get($url)->assertOk()->assertSee('lang="en"', false)
            ->assertSee('href="https://github.com/example"', false)->assertDontSee('javascript:alert(1)', false);
    }
});

it('BlogSettingsQuery returns slug locale timezone discoverable social_links and excludes quota', function () {
    $owner = tenantActiveUser('sq1');
    $blogId = app(CreateBlogAction::class)->execute(
        $owner,
        ['name' => 'Settings Q', 'slug' => 'settings-q-'.uniqid()],
    );

    $result = app(BlogSettingsQuery::class)->find($owner, $blogId);

    expect($result)->toHaveKeys([
        'name', 'slug', 'description', 'visibility', 'discoverable',
        'locale', 'timezone', 'posts_per_page', 'seo_title', 'seo_description', 'social_links',
    ]);
    expect(array_key_exists('storage_quota_bytes', $result))->toBeFalse();
    expect(array_key_exists('post_limit', $result))->toBeFalse();
});

it('saves a new slug normalized and query returns the normalized value', function () {
    $owner = tenantActiveUser('slugsave1');
    $blogId = app(CreateBlogAction::class)->execute(
        $owner,
        ['name' => 'Slug Save', 'slug' => 'slug-save-'.uniqid()],
    );
    tenantSettings($blogId);

    app(SaveBlogSettingsAction::class)->execute($owner, $blogId, [
        'name' => 'Slug Save',
        'slug' => 'My New Slug 123',
        'visibility' => 'PUBLIC',
        'posts_per_page' => 10,
    ]);

    $this->assertDatabaseHas('blogs', ['id' => $blogId, 'slug' => 'my-new-slug-123']);

    $result = app(BlogSettingsQuery::class)->find($owner, $blogId);
    expect($result['slug'])->toBe('my-new-slug-123');
});

it('rejects an empty slug after normalization with a validation error', function () {
    $owner = tenantActiveUser('emptyslug');
    $blogId = app(CreateBlogAction::class)->execute(
        $owner,
        ['name' => 'Empty Slug Test', 'slug' => 'empty-slug-'.uniqid()],
    );
    tenantSettings($blogId);

    expect(fn () => app(SaveBlogSettingsAction::class)->execute($owner, $blogId, [
        'name' => 'Empty Slug Test',
        'slug' => '!!!',
        'visibility' => 'PUBLIC',
        'posts_per_page' => 10,
    ]))->toThrow(ValidationException::class);
});

it('rejects a duplicate slug with a form validation error not an HTTP 500', function () {
    $owner = tenantActiveUser('dupslug1');
    $other = tenantActiveUser('dupslug2');
    $slugA = 'dup-slug-a-'.uniqid();
    $slugB = 'dup-slug-b-'.uniqid();

    tenantBlog($other, $slugA, 'PUBLISHED');

    $blogId = app(CreateBlogAction::class)->execute(
        $owner,
        ['name' => 'Blog B', 'slug' => $slugB],
    );
    tenantSettings($blogId);

    $caught = null;
    try {
        app(SaveBlogSettingsAction::class)->execute($owner, $blogId, [
            'name' => 'Blog B',
            'slug' => $slugA,
            'visibility' => 'PUBLIC',
            'posts_per_page' => 10,
        ]);
    } catch (ValidationException $e) {
        $caught = $e;
    }

    expect($caught)->not->toBeNull();
    expect($caught->errors())->toHaveKey('slug');
    $this->assertDatabaseHas('blogs', ['id' => $blogId, 'slug' => $slugB]);
});

it('saves discoverable flag to the blogs table via SaveBlogSettingsAction', function () {
    $owner = tenantActiveUser('discflag');
    $blogId = app(CreateBlogAction::class)->execute(
        $owner,
        ['name' => 'Disc Flag', 'slug' => 'disc-flag-'.uniqid()],
    );
    tenantSettings($blogId);

    app(SaveBlogSettingsAction::class)->execute($owner, $blogId, [
        'name' => 'Disc Flag',
        'slug' => DB::table('blogs')->where('id', $blogId)->value('slug'),
        'visibility' => 'PUBLIC',
        'discoverable' => false,
        'posts_per_page' => 10,
    ]);

    $this->assertDatabaseHas('blogs', ['id' => $blogId, 'discoverable' => 0]);
});

it('saves social_links as JSON stripping empty values and decodes them in query', function () {
    $owner = tenantActiveUser('social1');
    $blogId = app(CreateBlogAction::class)->execute(
        $owner,
        ['name' => 'Social', 'slug' => 'social-'.uniqid()],
    );
    tenantSettings($blogId);

    app(SaveBlogSettingsAction::class)->execute($owner, $blogId, [
        'name' => 'Social',
        'slug' => DB::table('blogs')->where('id', $blogId)->value('slug'),
        'visibility' => 'PUBLIC',
        'posts_per_page' => 10,
        'social_links' => ['twitter' => 'https://twitter.com/example', 'facebook' => ''],
    ]);

    $result = app(BlogSettingsQuery::class)->find($owner, $blogId);
    expect($result['social_links'])->toBe(['twitter' => 'https://twitter.com/example']);
    expect(array_key_exists('facebook', $result['social_links']))->toBeFalse();
});

it('ignores storage_quota_bytes and post_limit even when submitted by owner', function () {
    $owner = tenantActiveUser('quota1');
    $blogId = app(CreateBlogAction::class)->execute(
        $owner,
        ['name' => 'Quota Test', 'slug' => 'quota-test-'.uniqid()],
    );
    tenantSettings($blogId);

    $originalQuota = DB::table('blog_settings')->where('blog_id', $blogId)->value('storage_quota_bytes');

    app(SaveBlogSettingsAction::class)->execute($owner, $blogId, [
        'name' => 'Quota Test',
        'slug' => DB::table('blogs')->where('id', $blogId)->value('slug'),
        'visibility' => 'PUBLIC',
        'posts_per_page' => 10,
        'storage_quota_bytes' => 1,
        'post_limit' => 1,
    ]);

    $this->assertDatabaseHas('blog_settings', [
        'blog_id' => $blogId,
        'storage_quota_bytes' => $originalQuota,
    ]);
});

it('create blog form redirects back with errors preserving slug and description on slug collision', function () {
    $owner = tenantActiveUser('preserve1');
    $enteredSlug = 'Collision-Slug-'.uniqid();
    $normalizedSlug = Str::slug($enteredSlug);
    tenantBlog($owner, $normalizedSlug);

    $other = tenantActiveUser('preserve2');
    $templateId = tenantPublishedTemplate($other);

    $response = $this->actingAs($other)
        ->from('/workspace')
        ->post('/workspace/blogs', [
            'name' => 'Clash blog',
            'slug' => $enteredSlug,
            'description' => 'My description',
            'template_id' => $templateId,
        ]);

    $response->assertRedirect('/workspace')
        ->assertSessionHasErrors(['slug'])
        ->assertSessionDoesntHaveErrors(['template_id'])
        ->assertSessionHasInput('slug', $enteredSlug)
        ->assertSessionHasInput('description', 'My description');
});

it('CreateBlogAction falls back to name when slug is null, empty string, or whitespace', function () {
    $owner = tenantActiveUser('fallback1');

    // 1. null slug
    $name1 = 'Alpha Blog '.uniqid();
    $id1 = app(CreateBlogAction::class)->execute($owner, [
        'name' => $name1,
        'slug' => null,
    ]);
    expect(DB::table('blogs')->where('id', $id1)->value('slug'))->toBe(Str::slug($name1));

    // 2. empty string slug
    $name2 = 'Beta Blog '.uniqid();
    $id2 = app(CreateBlogAction::class)->execute($owner, [
        'name' => $name2,
        'slug' => '',
    ]);
    expect(DB::table('blogs')->where('id', $id2)->value('slug'))->toBe(Str::slug($name2));

    // 3. whitespace-only slug
    $name3 = 'Gamma Blog '.uniqid();
    $id3 = app(CreateBlogAction::class)->execute($owner, [
        'name' => $name3,
        'slug' => '    ',
    ]);
    expect(DB::table('blogs')->where('id', $id3)->value('slug'))->toBe(Str::slug($name3));
});

it('CreateBlogAction rejects creation when neither slug nor name can produce a valid slug', function () {
    $owner = tenantActiveUser('fallback2');

    // Name has no sluggable characters and slug is empty
    expect(fn () => app(CreateBlogAction::class)->execute($owner, [
        'name' => '??? !!!',
        'slug' => '',
    ]))->toThrow(ValidationException::class);

    // Name has no sluggable characters and slug is whitespace
    expect(fn () => app(CreateBlogAction::class)->execute($owner, [
        'name' => '??? !!!',
        'slug' => '   ',
    ]))->toThrow(ValidationException::class);
});

it('create blog form generates slug from name when slug is empty or whitespace', function () {
    $user = tenantActiveUser('formblank');
    $templateId = tenantPublishedTemplate($user);

    // Empty string slug
    $uniqueName1 = 'Blank Slug Blog '.uniqid();
    $response1 = $this->actingAs($user)
        ->post('/workspace/blogs', [
            'name' => $uniqueName1,
            'slug' => '',
            'description' => 'Test description',
            'template_id' => $templateId,
        ]);

    $expectedSlug1 = Str::slug($uniqueName1);
    $response1->assertRedirect();
    $this->assertDatabaseHas('blogs', [
        'name' => $uniqueName1,
        'slug' => $expectedSlug1,
        'owner_user_id' => $user->id,
    ]);

    // Whitespace slug
    $uniqueName2 = 'Whitespace Slug Blog '.uniqid();
    $response2 = $this->actingAs($user)
        ->post('/workspace/blogs', [
            'name' => $uniqueName2,
            'slug' => '   ',
            'description' => 'Test description 2',
            'template_id' => $templateId,
        ]);

    $expectedSlug2 = Str::slug($uniqueName2);
    $response2->assertRedirect();
    $this->assertDatabaseHas('blogs', [
        'name' => $uniqueName2,
        'slug' => $expectedSlug2,
        'owner_user_id' => $user->id,
    ]);
});

it('create blog form rejects unsluggable name when slug is omitted or empty', function () {
    $user = tenantActiveUser('unsluggable');
    $templateId = tenantPublishedTemplate($user);

    $response = $this->actingAs($user)
        ->from('/workspace')
        ->post('/workspace/blogs', [
            'name' => '??? ###',
            'slug' => '',
            'template_id' => $templateId,
        ]);

    $response->assertRedirect('/workspace')
        ->assertSessionHasErrors(['slug']);
});

it('Studio saveSettings reloads form and shows normalized slug after save', function () {
    $owner = tenantActiveUser('studioset1');
    $blogId = app(CreateBlogAction::class)->execute(
        $owner,
        ['name' => 'Studio Settings', 'slug' => 'studio-set-'.uniqid()],
    );
    tenantSettings($blogId);

    $this->actingAs($owner);
    Filament::setCurrentPanel(Filament::getPanel('blog'));

    Livewire::test(Studio::class, ['blogId' => $blogId])
        ->set('section', 'settings')
        ->set('form.name', 'Studio Settings')
        ->set('form.slug', 'Studio NEW Slug')
        ->set('form.visibility', 'PUBLIC')
        ->set('form.posts_per_page', 10)
        ->call('saveSettings')
        ->assertHasNoErrors()
        ->assertSet('form.slug', 'studio-new-slug');
});
