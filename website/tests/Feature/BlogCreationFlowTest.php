<?php

use Illuminate\Auth\GenericUser;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;

uses(DatabaseTransactions::class);

it('redirects a newly created blog to its management page', function (): void {
    $userId = (int) DB::table('users')
        ->where('status', 'ACTIVE')
        ->whereNull('deleted_at')
        ->value('id');
    expect($userId)->toBeGreaterThan(0);
    $user = new GenericUser((array) DB::table('users')->where('id', $userId)->first());
    $slug = 'design-flow-'.strtolower(bin2hex(random_bytes(4)));

    $response = $this->actingAs($user)->post(route('blogs.store'), [
        'name' => 'Design Flow Blog',
        'slug' => $slug,
        'description' => 'Choose a template next.',
    ]);

    $blogId = (int) DB::table('blogs')->where('slug', $slug)->value('id');
    expect($blogId)->toBeGreaterThan(0);
    $response->assertRedirect(route('blogs.show', $blogId));

    $this->actingAs($user)
        ->get(route('blogs.show', $blogId))
        ->assertOk()
        ->assertSee(__('template.home_and_layout'))
        ->assertSee(__('template.manage_posts'));
});

it('creates a new draft post when the optional slug field is omitted', function (): void {
    $blog = DB::table('blogs as b')
        ->join('users as u', 'u.id', '=', 'b.owner_user_id')
        ->where('u.status', 'ACTIVE')
        ->whereNull('u.deleted_at')
        ->whereNull('b.deleted_at')
        ->first(['b.id', 'b.owner_user_id']);
    $blogId = (int) ($blog->id ?? 0);
    expect($blogId)->toBeGreaterThan(0);
    $user = new GenericUser((array) DB::table('users')->where('id', $blog->owner_user_id)->first());

    $this->actingAs($user)
        ->post(route('blogs.posts.store', $blogId), ['title' => 'New Article'])
        ->assertRedirect();

    expect(
        DB::table('posts')->where('blog_id', $blogId)->where('slug', 'new-article')->exists(),
    )->toBeTrue();
});

it('updates blog publication status and visibility as separate values', function (): void {
    $userId = (int) DB::table('users')
        ->where('status', 'ACTIVE')
        ->whereNull('deleted_at')
        ->value('id');
    $user = new GenericUser((array) DB::table('users')->where('id', $userId)->first());
    $slug = 'update-blog-'.strtolower(bin2hex(random_bytes(4)));
    $blogId = (int) DB::table('blogs')->insertGetId([
        'owner_user_id' => $userId,
        'name' => 'Blog Before Update',
        'slug' => $slug,
        'description' => null,
        'status' => 'DRAFT',
        'visibility' => 'PUBLIC',
        'discoverable' => false,
        'published_at' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs($user)
        ->put(route('blogs.update', $blogId), [
            'name' => 'Blog After Update',
            'slug' => $slug,
            'description' => '',
            'status' => 'PUBLISHED',
            'visibility' => 'PUBLIC',
            'discoverable' => '0',
        ])
        ->assertRedirect(route('blogs.show', $blogId));

    expect(DB::table('blogs')->where('id', $blogId)->value('status'))
        ->toBe('PUBLISHED')
        ->and(DB::table('blogs')->where('id', $blogId)->value('visibility'))
        ->toBe('PUBLIC');
});
