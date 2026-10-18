<?php

use Illuminate\Auth\GenericUser;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

uses(DatabaseTransactions::class);

function profileTestUser(int $id): GenericUser
{
    return new GenericUser((array) DB::table('users')->where('id', $id)->first());
}

it('requires authentication to view the profile', function (): void {
    $this->get(route('profile.edit'))->assertRedirect(route('login'));
});

it('shows the signed-in users profile', function (): void {
    $userId = (int) DB::table('users')->whereNull('deleted_at')->value('id');
    $user = profileTestUser($userId);

    $this->actingAs($user)
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertSee($user->name)
        ->assertSee($user->email)
        ->assertSee(route('blogs.index'), false)
        ->assertSee(route('blogs.create'), false)
        ->assertDontSee('/my-posts', false);
});

it('updates only the signed-in users display name', function (): void {
    $ids = DB::table('users')->whereNull('deleted_at')->orderBy('id')->limit(2)->pluck('id')->map(fn ($id) => (int) $id);
    expect($ids)->toHaveCount(2);
    $otherName = (string) DB::table('users')->where('id', $ids[1])->value('name');

    $this->actingAs(profileTestUser($ids[0]))
        ->put(route('profile.update'), ['name' => 'Updated Profile Name', 'remove_avatar' => '0'])
        ->assertRedirect();

    expect(DB::table('users')->where('id', $ids[0])->value('name'))->toBe('Updated Profile Name')
        ->and(DB::table('users')->where('id', $ids[1])->value('name'))->toBe($otherName);
});

it('uploads a profile image to local storage', function (): void {
    Storage::fake('public');
    config(['media.driver' => 'local', 'media.local_disk' => 'public']);
    $userId = (int) DB::table('users')->whereNull('deleted_at')->value('id');
    $source = tempnam(sys_get_temp_dir(), 'profile-avatar-');
    file_put_contents($source, base64_decode(
        'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=',
        true,
    ));

    try {
        $this->actingAs(profileTestUser($userId))
            ->put(route('profile.update'), [
                'name' => (string) DB::table('users')->where('id', $userId)->value('name'),
                'remove_avatar' => '0',
                'avatar' => new UploadedFile($source, 'avatar.png', 'image/png', null, true),
            ])
            ->assertRedirect();
    } finally {
        @unlink($source);
    }

    $mediaId = DB::table('users')->where('id', $userId)->value('avatar_media_id');
    expect($mediaId)->not->toBeNull();

    $asset = DB::table('media_assets')->where('id', $mediaId)->first();
    expect($asset->scope)->toBe('USER')
        ->and((int) $asset->uploaded_by)->toBe($userId)
        ->and($asset->status)->toBe('READY');
    Storage::disk('public')->assertExists($asset->cloudinary_public_id);
});

it('saves name avatar and password in one request', function (): void {
    Storage::fake('public');
    config(['media.driver' => 'local', 'media.local_disk' => 'public']);
    $now = now();
    $userId = (int) DB::table('users')->insertGetId([
        'name' => 'Combined Save User',
        'email' => 'combined-save-'.uniqid().'@example.com',
        'email_verified_at' => $now,
        'password' => Hash::make('current-password'),
        'status' => 'ACTIVE',
        'created_at' => $now,
        'updated_at' => $now,
    ]);
    $source = tempnam(sys_get_temp_dir(), 'profile-combined-');
    file_put_contents($source, base64_decode(
        'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=',
        true,
    ));

    try {
        $this->actingAs(profileTestUser($userId))
            ->put(route('profile.update'), [
                'name' => 'Everything Updated',
                'remove_avatar' => '0',
                'avatar' => new UploadedFile($source, 'avatar.png', 'image/png', null, true),
                'current_password' => 'current-password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])
            ->assertRedirect(route('login'));
    } finally {
        @unlink($source);
    }

    $user = DB::table('users')->where('id', $userId)->first();
    expect($user->name)->toBe('Everything Updated')
        ->and($user->avatar_media_id)->not->toBeNull()
        ->and(Hash::check('new-password', $user->password))->toBeTrue();
});
