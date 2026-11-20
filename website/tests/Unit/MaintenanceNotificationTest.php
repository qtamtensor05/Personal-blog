<?php

use App\Infrastructure\Notifications\QueryBuilderMaintenanceNotificationPublisher;
use App\Infrastructure\Notifications\QueryBuilderNotificationRepository;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    // This test owns a minimal schema, independent of the migrated CI database.
    config()->set('database.connections.maintenance_notification_test', [
        'driver' => 'sqlite',
        'database' => ':memory:',
        'prefix' => '',
    ]);
    DB::setDefaultConnection('maintenance_notification_test');
});

afterEach(function (): void {
    DB::purge('maintenance_notification_test');
});

it('broadcasts once per user and keeps read state independent', function (): void {
    DB::statement('CREATE TABLE users (id INTEGER PRIMARY KEY, deleted_at TEXT NULL)');
    DB::statement('CREATE TABLE notifications (id TEXT PRIMARY KEY, recipient_user_id INTEGER, type TEXT, data TEXT, read_at TEXT NULL, created_at TEXT, updated_at TEXT)');
    DB::table('users')->insert([['id' => 1, 'deleted_at' => null], ['id' => 2, 'deleted_at' => null], ['id' => 3, 'deleted_at' => '2026-01-01']]);
    $publisher = new QueryBuilderMaintenanceNotificationPublisher;
    $publisher->publish('Maintenance <script>unsafe</script>', 1);
    $publisher->publish('Maintenance <script>unsafe</script>', 1);
    expect(DB::table('notifications')->count())->toBe(2);
    $repository = new QueryBuilderNotificationRepository;
    expect($repository->unreadCount(1))->toBe(1)->and($repository->unreadCount(2))->toBe(1);
    $notification = $repository->paginateForRecipient(1, 1, 10)->items[0];
    expect($notification->data['message'])->toBe('Maintenance <script>unsafe</script>');
    $repository->markReadForRecipient($notification->id, 1);
    expect($repository->unreadCount(1))->toBe(0)->and($repository->unreadCount(2))->toBe(1);
    $publisher->publish('New message', 2);
    expect($repository->unreadCount(1))->toBe(1)->and($repository->unreadCount(2))->toBe(2);
});
