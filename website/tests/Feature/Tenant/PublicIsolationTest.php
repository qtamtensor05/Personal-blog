<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;

it('does not expose a post through another blog slug', function () {
    $owner = User::factory()->create(['status' => 'ACTIVE', 'email_verified_at' => now()]);
    $a = DB::table('blogs')->insertGetId([
        'owner_user_id' => $owner->id,
        'name' => 'A',
        'slug' => 'a',
        'status' => 'PUBLISHED',
        'visibility' => 'PUBLIC',
        'discoverable' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $b = DB::table('blogs')->insertGetId([
        'owner_user_id' => $owner->id,
        'name' => 'B',
        'slug' => 'b',
        'status' => 'PUBLISHED',
        'visibility' => 'PUBLIC',
        'discoverable' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    DB::table('posts')->insert([
        'blog_id' => $a,
        'title' => 'Secret',
        'slug' => 'secret',
        'author_user_id' => $owner->id,
        'content_json' => json_encode(['type' => 'doc', 'content' => []]),
        'status' => 'PUBLISHED',
        'updated_by' => $owner->id,
        'published_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $this->get('/b/b/posts/secret')->assertNotFound();
    $this->get('/b/a/posts/secret')->assertOk();
});
