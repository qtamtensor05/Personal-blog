<?php

declare(strict_types=1);

use Illuminate\Auth\GenericUser;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;

uses(DatabaseTransactions::class);

function publicProfileUser(): GenericUser
{
    $id = DB::table('users')->insertGetId([
        'name' => 'Public Profile Reader',
        'email' => 'public-profile-'.bin2hex(random_bytes(5)).'@example.test',
        'password' => 'not-used',
        'status' => 'ACTIVE',
        'email_verified_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return new GenericUser((array) DB::table('users')->where('id', $id)->first());
}

function publicProfileBlog(int $ownerId, string $slug, string $name, string $status = 'PUBLISHED', string $visibility = 'PUBLIC', bool $discoverable = true): int
{
    return (int) DB::table('blogs')->insertGetId([
        'owner_user_id' => $ownerId,
        'name' => $name,
        'slug' => $slug,
        'status' => $status,
        'visibility' => $visibility,
        'discoverable' => $discoverable,
        'published_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

beforeEach(function (): void {
    if (DB::getDriverName() !== 'mysql' || ! str_contains(DB::connection()->getDatabaseName(), '_test_')) {
        throw new RuntimeException('Public profile tests require isolated MySQL *_test_* database.');
    }
});

it('shows only public identity and discoverable public blogs to guests', function (): void {
    $user = publicProfileUser();
    $prefix = 'profile-'.bin2hex(random_bytes(4));
    publicProfileBlog((int) $user->id, $prefix.'-public', 'Visible Profile Blog');
    publicProfileBlog((int) $user->id, $prefix.'-private', 'Private Profile Blog', visibility: 'PRIVATE');
    publicProfileBlog((int) $user->id, $prefix.'-unlisted', 'Unlisted Profile Blog', visibility: 'UNLISTED');
    publicProfileBlog((int) $user->id, $prefix.'-hidden', 'Hidden Profile Blog', status: 'HIDDEN');
    publicProfileBlog((int) $user->id, $prefix.'-undiscoverable', 'Undiscoverable Profile Blog', discoverable: false);

    $this->get(route('profiles.show', $user->id))->assertOk()
        ->assertSee($user->name)
        ->assertSee('Visible Profile Blog')
        ->assertDontSee($user->email)
        ->assertDontSee('Private Profile Blog')
        ->assertDontSee('Unlisted Profile Blog')
        ->assertDontSee('Hidden Profile Blog')
        ->assertDontSee('Undiscoverable Profile Blog');
});

it('links real post author and catalog avatars to the public profile without a reading-time label', function (): void {
    $user = publicProfileUser();
    $slug = 'profile-post-'.bin2hex(random_bytes(4));
    $blogId = publicProfileBlog((int) $user->id, $slug, 'Public Profile Blog');
    DB::table('posts')->insert([
        'blog_id' => $blogId,
        'title' => 'Profile Link Post',
        'slug' => 'entry',
        'author_user_id' => $user->id,
        'content_json' => json_encode(['type' => 'doc', 'content' => [['type' => 'paragraph']]]),
        'status' => 'PUBLISHED',
        'published_at' => now(),
        'updated_by' => $user->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $profileUrl = route('profiles.show', $user->id);

    $this->get(route('public.post', [$slug, 'entry']))->assertOk()
        ->assertSee('href="'.$profileUrl.'"', false);
    $this->get(route('posts.index'))->assertOk()
        ->assertSee('Profile Link Post')
        ->assertSee('href="'.$profileUrl.'"', false)
        ->assertDontSee(__('post.minutes_read', ['minutes' => 5]));
    $this->get(route('posts.show', 'mock-entry'))->assertOk()
        ->assertDontSee(__('post.minutes_read', ['minutes' => 5]));

    $this->actingAs($user)->get(route('profile.edit'))->assertOk()
        ->assertSee('href="'.$profileUrl.'"', false);
});

it('returns not found for missing inactive and deleted profiles', function (): void {
    $user = publicProfileUser();
    $url = route('profiles.show', $user->id);
    DB::table('users')->where('id', $user->id)->update(['status' => 'LOCKED']);
    $this->get($url)->assertNotFound();

    DB::table('users')->where('id', $user->id)->update(['status' => 'ACTIVE', 'deleted_at' => now()]);
    $this->get($url)->assertNotFound();
    $this->get('/users/not-a-number')->assertNotFound();
});
