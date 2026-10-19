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
    $response->assertRedirect(route('blogs.design.edit', $blogId));

    $this->actingAs($user)
        ->get(route('blogs.design.edit', $blogId))
        ->assertOk()
        ->assertSee('Choose an admin template')
        ->assertSee('Tạp chí tối giản');
});
