<?php

use App\Application\Comments\CommentManager;
use App\Application\Moderation\Contracts\ContentReportRepository;
use App\Application\Moderation\Contracts\ReportNotificationRecipients;
use App\Application\Notifications\Contracts\NotificationPublisher;
use App\Application\Notifications\Contracts\NotificationRepository;
use App\Infrastructure\Moderation\QueryBuilderContentReportRepository;
use App\Infrastructure\Moderation\QueryBuilderReportNotificationRecipients;
use App\Infrastructure\Moderation\ReportNotificationOutbox;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    $this->previousConnection = DB::getDefaultConnection();
    config()->set('database.connections.report_notification_test', [
        'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
    ]);
    DB::setDefaultConnection('report_notification_test');
});

afterEach(function (): void {
    DB::purge('report_notification_test');
    DB::setDefaultConnection($this->previousConnection);
});

function reportNotificationSchema(): void
{
    DB::statement('CREATE TABLE users (id INTEGER PRIMARY KEY)');
    DB::table('users')->insert(['id' => 7]);
    DB::statement('CREATE TABLE content_reports (id INTEGER PRIMARY KEY AUTOINCREMENT, blog_id INTEGER, post_id INTEGER NULL, comment_id INTEGER NULL, reporter_user_id INTEGER, target_type TEXT, reason TEXT, status TEXT, created_at TEXT, updated_at TEXT)');
    (require database_path('migrations/2026_11_22_000000_create_report_notification_outbox.php'))->up();
    DB::statement('CREATE TABLE notifications (id TEXT PRIMARY KEY, recipient_user_id INTEGER, type TEXT, data TEXT, read_at TEXT NULL, created_at TEXT, updated_at TEXT)');
}

function reportNotificationOutbox(array $ids = [1, 2]): ReportNotificationOutbox
{
    $recipients = Mockery::mock(ReportNotificationRecipients::class);
    $recipients->shouldReceive('userIds')->andReturn($ids);

    return new ReportNotificationOutbox($recipients, app(NotificationPublisher::class));
}

it('batches recipient permissions with expiry, deny overrides and direct grants', function (): void {
    DB::statement('CREATE TABLE users (id INTEGER PRIMARY KEY, status TEXT, deleted_at TEXT NULL)');
    DB::statement('CREATE TABLE permissions (id INTEGER PRIMARY KEY, code TEXT)');
    DB::statement('CREATE TABLE user_platform_roles (user_id INTEGER, role_id INTEGER, expires_at TEXT NULL)');
    DB::statement('CREATE TABLE platform_role_permissions (role_id INTEGER, permission_id INTEGER)');
    DB::statement('CREATE TABLE user_platform_permissions (user_id INTEGER, permission_id INTEGER, effect TEXT)');
    foreach (range(1, 200) as $id) {
        DB::table('users')->insert(['id' => $id, 'status' => $id === 6 ? 'LOCKED' : 'ACTIVE', 'deleted_at' => $id === 7 ? '2026-01-01' : null]);
    }
    DB::table('permissions')->insert([['id' => 1, 'code' => 'admin.access'], ['id' => 2, 'code' => 'content_reports.manage']]);
    DB::table('platform_role_permissions')->insert([['role_id' => 1, 'permission_id' => 1], ['role_id' => 1, 'permission_id' => 2]]);
    foreach (range(1, 7) as $id) {
        DB::table('user_platform_roles')->insert(['user_id' => $id, 'role_id' => 1, 'expires_at' => $id === 3 ? '2000-01-01' : null]);
    }
    DB::table('user_platform_permissions')->insert([
        ['user_id' => 2, 'permission_id' => 2, 'effect' => 'DENY'],
        ['user_id' => 5, 'permission_id' => 1, 'effect' => 'DENY'],
        ['user_id' => 8, 'permission_id' => 1, 'effect' => 'ALLOW'],
        ['user_id' => 8, 'permission_id' => 2, 'effect' => 'ALLOW'],
        ['user_id' => 9, 'permission_id' => 2, 'effect' => 'ALLOW'],
    ]);
    DB::enableQueryLog();
    DB::flushQueryLog();
    expect((new QueryBuilderReportNotificationRecipients)->userIds())->toBe([1, 4, 8]);
    expect(DB::getQueryLog())->toHaveCount(1);
    DB::disableQueryLog();
});

it('persists reports with outbox work and delivers each target exactly once', function (string $type): void {
    reportNotificationSchema();
    $outbox = reportNotificationOutbox();
    $repository = new QueryBuilderContentReportRepository($outbox);
    $id = $type === 'COMMENT'
        ? $repository->createComment(10, 20, 30, 7, 'Spam comment')
        : $repository->create(10, $type === 'POST' ? 20 : null, 7, 'Spam content');
    $outbox->deliver($id);
    expect(DB::table('content_reports')->count())->toBe(1)
        ->and(DB::table('notifications')->count())->toBe(2)
        ->and(DB::table('report_notification_outbox')->value('delivered_at'))->not->toBeNull();
    $data = json_decode(DB::table('notifications')->value('data'), true);
    expect($data['target_type'])->toBe($type)->and($data['report_id'])->toBe($id);
    expect(fn () => $type === 'COMMENT'
        ? $repository->createComment(10, 20, 30, 7, 'Spam again')
        : $repository->create(10, $type === 'POST' ? 20 : null, 7, 'Spam again'))->toThrow(DomainException::class);
    expect(DB::table('content_reports')->count())->toBe(1)->and(DB::table('notifications')->count())->toBe(2);
})->with(['BLOG', 'POST', 'COMMENT']);

it('retains retryable work on lookup failure without rejecting the saved report', function (): void {
    reportNotificationSchema();
    $recipients = Mockery::mock(ReportNotificationRecipients::class);
    $recipients->shouldReceive('userIds')->once()->andThrow(new RuntimeException('lookup unavailable'));
    $broken = new ReportNotificationOutbox($recipients, app(NotificationPublisher::class));
    $repository = new QueryBuilderContentReportRepository($broken);
    $id = $repository->create(10, null, 7, 'Spam content');
    expect(DB::table('content_reports')->count())->toBe(1)
        ->and(DB::table('report_notification_outbox')->value('delivered_at'))->toBeNull();
    reportNotificationOutbox()->drain();
    expect(DB::table('notifications')->count())->toBe(2)
        ->and(DB::table('report_notification_outbox')->where('report_id', $id)->value('delivered_at'))->not->toBeNull();
});

it('rolls back partial delivery and retries without duplicate notifications', function (): void {
    reportNotificationSchema();
    $recipients = Mockery::mock(ReportNotificationRecipients::class);
    $recipients->shouldReceive('userIds')->andReturn([1, 2]);
    $publisher = Mockery::mock(NotificationPublisher::class);
    $publisher->shouldReceive('publish')->with(1, 'moderation.report_received', Mockery::any())->once()->andReturnUsing(fn ($id, $type, $data) => app(NotificationRepository::class)->create($id, $type, $data));
    $publisher->shouldReceive('publish')->with(2, 'moderation.report_received', Mockery::any())->once()->andThrow(new RuntimeException('publish failed'));
    $repository = new QueryBuilderContentReportRepository(new ReportNotificationOutbox($recipients, $publisher));
    $id = $repository->createComment(10, 20, 30, 7, 'Spam comment');
    expect(DB::table('notifications')->count())->toBe(0);
    $outbox = reportNotificationOutbox();
    $outbox->deliver($id);
    $outbox->deliver($id);
    expect(DB::table('notifications')->count())->toBe(2);
});

it('rolls back the report if durable outbox insertion fails', function (): void {
    reportNotificationSchema();
    DB::statement('DROP TABLE report_notification_outbox');
    $repository = new QueryBuilderContentReportRepository(reportNotificationOutbox());
    expect(fn () => $repository->create(10, null, 7, 'Spam content'))->toThrow(QueryException::class);
    expect(DB::table('content_reports')->count())->toBe(0);
});

it('routes the public comment manager through moderation and its notification outbox', function (): void {
    reportNotificationSchema();
    DB::statement('CREATE TABLE comments (id INTEGER PRIMARY KEY, post_id INTEGER, deleted_at TEXT NULL)');
    DB::table('comments')->insert(['id' => 30, 'post_id' => 20, 'deleted_at' => null]);
    app()->instance(ContentReportRepository::class, new QueryBuilderContentReportRepository(reportNotificationOutbox()));
    $manager = app(CommentManager::class);
    $actor = (object) ['id' => 7, 'status' => 'ACTIVE', 'email_verified_at' => '2026-01-01'];
    $manager->report($actor, 10, 20, 30, 'Spam comment');
    expect(DB::table('notifications')->count())->toBe(2);
    expect(fn () => $manager->report($actor, 10, 20, 30, 'Spam again'))->toThrow(ValidationException::class);
    expect(DB::table('content_reports')->count())->toBe(1);
});
