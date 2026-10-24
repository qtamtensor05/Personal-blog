<?php

use Illuminate\Auth\GenericUser;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

uses(DatabaseTransactions::class);

function notificationTestUser(int $id): GenericUser
{
    $row = DB::table('users')->where('id', $id)->first();

    return new GenericUser((array) $row);
}

it('requires authentication to view notifications', function (): void {
    $this->get(route('notifications.index'))->assertRedirect(route('login'));
});

it('prevents IDOR when marking a notification as read', function (): void {
    $ids = DB::table('users')
        ->whereNull('deleted_at')
        ->orderBy('id')
        ->limit(2)
        ->pluck('id')
        ->map(fn ($id) => (int) $id);
    expect($ids)->toHaveCount(2);

    $notificationId = (string) Str::uuid();
    DB::table('notifications')->insert([
        'id' => $notificationId,
        'recipient_user_id' => $ids[1],
        'type' => 'test.private',
        'data' => json_encode(['title' => 'Private notification'], JSON_THROW_ON_ERROR),
        'read_at' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs(notificationTestUser($ids[0]))
        ->patch(route('notifications.read', $notificationId))
        ->assertNotFound();

    expect(DB::table('notifications')->where('id', $notificationId)->value('read_at'))->toBeNull();
});

it('paginates only the signed-in users notifications', function (): void {
    $ids = DB::table('users')
        ->whereNull('deleted_at')
        ->orderBy('id')
        ->limit(2)
        ->pluck('id')
        ->map(fn ($id) => (int) $id);
    expect($ids)->toHaveCount(2);

    $rows = [];
    for ($index = 1; $index <= 16; $index++) {
        $time = now()->subSeconds(20 - $index);
        $rows[] = [
            'id' => (string) Str::uuid(),
            'recipient_user_id' => $ids[0],
            'type' => 'test.pagination',
            'data' => json_encode(['title' => "Own notification {$index}"], JSON_THROW_ON_ERROR),
            'read_at' => null,
            'created_at' => $time,
            'updated_at' => $time,
        ];
    }

    $rows[] = [
        'id' => (string) Str::uuid(),
        'recipient_user_id' => $ids[1],
        'type' => 'test.private',
        'data' => json_encode(['title' => 'Other users secret'], JSON_THROW_ON_ERROR),
        'read_at' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ];
    DB::table('notifications')->insert($rows);

    $this->actingAs(notificationTestUser($ids[0]))
        ->get(route('notifications.index', ['page' => 2]))
        ->assertOk()
        ->assertSee(__('notifications.page_of', ['page' => 2, 'total' => 2]))
        ->assertSee('Own notification 1')
        ->assertDontSee('Other users secret');
});
