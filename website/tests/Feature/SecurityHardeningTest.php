<?php

use App\Models\User;
use App\Modules\Audit\DatabaseAuditRecorder;
use App\Modules\Content\Actions\PublishPostAction;
use App\Modules\Content\Actions\PublishScheduledContentAction;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;

it('does not reactivate a suspended account through email verification', function () {
    $user = User::factory()->create(['status' => 'SUSPENDED', 'email_verified_at' => null]);
    $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(10), [
        'id' => $user->id, 'hash' => sha1($user->email),
    ]);
    $this->actingAs($user)->get($url)->assertRedirect();
    $this->assertDatabaseHas('users', ['id' => $user->id, 'status' => 'SUSPENDED']);
});

it('publishes array JSON drafts and rejects locked actors', function () {
    $owner = User::factory()->create(['status' => 'ACTIVE']);
    $blog = createSecurityBlog($owner, 'json-draft');
    $document = ['type' => 'doc', 'content' => [['type' => 'paragraph']]];
    $post = DB::table('posts')->insertGetId([
        'blog_id' => $blog, 'title' => 'Original', 'slug' => 'json',
        'author_user_id' => $owner->id, 'updated_by' => $owner->id,
        'content_json' => json_encode(['type' => 'doc', 'content' => []]),
        'draft_data' => json_encode(['title' => 'Updated', 'content_json' => $document]),
        'status' => 'DRAFT', 'created_at' => now(), 'updated_at' => now(),
    ]);
    $owner->forceFill(['locked_until' => now()->addHour()])->save();
    expect(fn () => app(PublishPostAction::class)->execute($owner, $blog, $post))
        ->toThrow(AuthorizationException::class);
    $owner->forceFill(['locked_until' => null])->save();
    app(PublishPostAction::class)->execute($owner, $blog, $post);
    $row = DB::table('posts')->where('id', $post)->first();
    expect(json_decode($row->content_json, true))->toBe($document)
        ->and($row->draft_data)->toBeNull()->and($row->title)->toBe('Updated');
});

it('skips scheduled content for locked owners and deleted records', function () {
    $owner = User::factory()->create(['status' => 'ACTIVE', 'locked_until' => now()->addHour()]);
    $blog = createSecurityBlog($owner, 'skip-scheduled');
    $post = DB::table('posts')->insertGetId([
        'blog_id' => $blog, 'title' => 'Scheduled', 'slug' => 'skip',
        'author_user_id' => $owner->id, 'updated_by' => $owner->id,
        'content_json' => json_encode(['type' => 'doc', 'content' => []]),
        'status' => 'SCHEDULED', 'scheduled_at' => now()->subMinute(),
        'created_at' => now(), 'updated_at' => now(),
    ]);
    expect(app(PublishScheduledContentAction::class)->execute())->toBe(0);
    $owner->forceFill(['locked_until' => null])->save();
    DB::table('posts')->where('id', $post)->update(['deleted_at' => now()]);
    expect(app(PublishScheduledContentAction::class)->execute())->toBe(0);
    $this->assertDatabaseCount('admin_audit_logs', 0);
});

function createSecurityBlog(User $owner, string $slug): int
{
    return DB::table('blogs')->insertGetId([
        'owner_user_id' => $owner->id,
        'name' => strtoupper($slug),
        'slug' => $slug,
        'status' => 'PUBLISHED',
        'visibility' => 'PUBLIC',
        'discoverable' => 1,
        'published_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

it('rejects a login while the account lock is active', function () {
    $user = User::factory()->create([
        'status' => 'ACTIVE',
        'email_verified_at' => now(),
        'locked_until' => now()->addHour(),
        'password' => 'correct-password',
    ]);

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'correct-password',
    ])->assertSessionHasErrors('email');

    $this->assertGuest();
});

it('does not render media owned by another blog', function () {
    $ownerA = User::factory()->create(['status' => 'ACTIVE']);
    $ownerB = User::factory()->create(['status' => 'ACTIVE']);
    $blogA = createSecurityBlog($ownerA, 'tenant-a');
    $blogB = createSecurityBlog($ownerB, 'tenant-b');
    $mediaId = DB::table('media_assets')->insertGetId([
        'scope' => 'BLOG',
        'blog_id' => $blogB,
        'uploaded_by' => $ownerB->id,
        'cloud_name' => 'test',
        'cloudinary_asset_id' => 'asset-b',
        'cloudinary_public_id' => 'private-b',
        'secure_url' => 'https://example.test/tenant-b-secret.png',
        'original_name' => 'secret.png',
        'mime_type' => 'image/png',
        'status' => 'READY',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    DB::table('posts')->insert([
        'blog_id' => $blogA,
        'title' => 'Post A',
        'slug' => 'post-a',
        'author_user_id' => $ownerA->id,
        'content_json' => json_encode([
            'type' => 'doc',
            'content' => [['type' => 'image', 'attrs' => ['mediaId' => $mediaId]]],
        ], JSON_THROW_ON_ERROR),
        'status' => 'PUBLISHED',
        'updated_by' => $ownerA->id,
        'published_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->get('/b/tenant-a/posts/post-a')
        ->assertOk()
        ->assertDontSee('tenant-b-secret.png', false);
});

it('refuses to publish a post that references media from another blog', function () {
    $ownerA = User::factory()->create(['status' => 'ACTIVE']);
    $ownerB = User::factory()->create(['status' => 'ACTIVE']);
    $blogA = createSecurityBlog($ownerA, 'publish-a');
    $blogB = createSecurityBlog($ownerB, 'publish-b');
    $mediaId = DB::table('media_assets')->insertGetId([
        'scope' => 'BLOG',
        'blog_id' => $blogB,
        'uploaded_by' => $ownerB->id,
        'cloud_name' => 'test',
        'cloudinary_asset_id' => 'publish-asset-b',
        'cloudinary_public_id' => 'publish-private-b',
        'secure_url' => 'https://example.test/publish-b.png',
        'original_name' => 'publish-b.png',
        'mime_type' => 'image/png',
        'status' => 'READY',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $postId = DB::table('posts')->insertGetId([
        'blog_id' => $blogA,
        'title' => 'Draft',
        'slug' => 'draft',
        'author_user_id' => $ownerA->id,
        'content_json' => json_encode(['type' => 'doc', 'content' => []]),
        'draft_data' => json_encode([
            'content_json' => [
                'type' => 'doc',
                'content' => [['type' => 'image', 'attrs' => ['mediaId' => $mediaId]]],
            ],
        ], JSON_THROW_ON_ERROR),
        'status' => 'DRAFT',
        'updated_by' => $ownerA->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    expect(fn () => app(PublishPostAction::class)->execute($ownerA, $blogA, $postId))
        ->toThrow(ValidationException::class);
    $this->assertDatabaseHas('posts', ['id' => $postId, 'status' => 'DRAFT']);
});

it('writes audit data to the canonical columns', function () {
    $owner = User::factory()->create(['status' => 'ACTIVE']);
    $blogId = createSecurityBlog($owner, 'audit-blog');

    (new DatabaseAuditRecorder)->record(
        $owner->id,
        'security.test',
        'blog',
        $blogId,
        $blogId,
        ['api_token' => 'secret-value'],
        ['name' => 'Safe'],
    );

    $log = DB::table('admin_audit_logs')->first();
    expect($log->before_data)->toContain('[REDACTED]')
        ->and($log->before_data)->not->toContain('secret-value')
        ->and($log->after_data)->toContain('Safe');
});

it('publishes scheduled content with a valid audit actor', function () {
    $owner = User::factory()->create(['status' => 'ACTIVE']);
    $blogId = createSecurityBlog($owner, 'scheduled-blog');
    $postId = DB::table('posts')->insertGetId([
        'blog_id' => $blogId,
        'title' => 'Scheduled',
        'slug' => 'scheduled',
        'author_user_id' => $owner->id,
        'content_json' => json_encode(['type' => 'doc', 'content' => []]),
        'status' => 'SCHEDULED',
        'scheduled_at' => now()->subMinute(),
        'updated_by' => $owner->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    expect(app(PublishScheduledContentAction::class)->execute())->toBe(1);
    $this->assertDatabaseHas('posts', ['id' => $postId, 'status' => 'PUBLISHED']);
    $this->assertDatabaseHas('admin_audit_logs', [
        'actor_user_id' => $owner->id,
        'resource_id' => (string) $postId,
    ]);
});
