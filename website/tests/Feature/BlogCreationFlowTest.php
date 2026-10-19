<?php

use Illuminate\Auth\GenericUser;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;

uses(DatabaseTransactions::class);

it('redirects a newly created blog to the admin template picker', function (): void {
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
        ->assertSee('Choose an admin template')
        ->assertSee('Tạp chí tối giản');
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

    $this->actingAs($user)->put(route('blogs.update', $blogId), [
        'name' => 'Blog After Update',
        'slug' => $slug,
        'description' => '',
        'status' => 'PUBLISHED',
        'visibility' => 'PUBLIC',
        'discoverable' => '0',
    ])->assertRedirect(route('blogs.show', $blogId));

    expect(DB::table('blogs')->where('id', $blogId)->value('status'))->toBe('PUBLISHED')
        ->and(DB::table('blogs')->where('id', $blogId)->value('visibility'))->toBe('PUBLIC');
});
