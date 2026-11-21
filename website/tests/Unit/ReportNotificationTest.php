<?php

use App\Application\AccessControl\Contracts\PermissionRepository;
use App\Application\Moderation\Contracts\ContentReportRepository;
use App\Application\Moderation\Contracts\ReportNotificationRecipients;
use App\Application\Moderation\UseCases\ModerateContent\ModerateContentService;
use App\Application\Notifications\Contracts\NotificationPublisher;
use App\Infrastructure\Moderation\QueryBuilderReportNotificationRecipients;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

uses(TestCase::class);

it('only selects active users with both effective admin and moderation permissions', function (): void {
    DB::statement('CREATE TABLE users (id INTEGER PRIMARY KEY, status TEXT, deleted_at TEXT NULL)');
    DB::table('users')->insert([
        ['id' => 1, 'status' => 'ACTIVE', 'deleted_at' => null],
        ['id' => 2, 'status' => 'ACTIVE', 'deleted_at' => null],
        ['id' => 3, 'status' => 'LOCKED', 'deleted_at' => null],
        ['id' => 4, 'status' => 'ACTIVE', 'deleted_at' => '2026-01-01'],
        ['id' => 5, 'status' => 'ACTIVE', 'deleted_at' => null],
    ]);
    $permissions = Mockery::mock(PermissionRepository::class);
    $permissions->shouldReceive('codesForUser')->with(1)->once()->andReturn(['admin.access', 'content_reports.manage']);
    $permissions->shouldReceive('codesForUser')->with(2)->once()->andReturn(['admin.access']);
    $permissions->shouldReceive('codesForUser')->with(5)->once()->andReturn(['content_reports.manage']);
    expect((new QueryBuilderReportNotificationRecipients($permissions))->userIds())->toBe([1]);
});

it('notifies all moderators for a new comment report and rejects duplicates without notifying', function (): void {
    $reports = Mockery::mock(ContentReportRepository::class);
    $reports->shouldReceive('hasPendingCommentReport')->with(7, 30)->twice()->andReturn(false, true);
    $reports->shouldReceive('createComment')->with(10, 20, 30, 7, 'Spam comment')->once()->andReturn(42);
    $recipients = Mockery::mock(ReportNotificationRecipients::class);
    $recipients->shouldReceive('userIds')->once()->andReturn([1, 2]);
    $notifications = Mockery::mock(NotificationPublisher::class);
    foreach ([1, 2] as $id) {
        $notifications->shouldReceive('publish')->with($id, 'moderation.report_received', Mockery::on(fn ($data) => $data['report_id'] === 42 && $data['target_type'] === 'COMMENT'))->once()->andReturn('notification-'.$id);
    }
    app()->instance(ContentReportRepository::class, $reports);
    app()->instance(ReportNotificationRecipients::class, $recipients);
    app()->instance(NotificationPublisher::class, $notifications);
    $service = app(ModerateContentService::class);
    $service->reportComment(7, 10, 20, 30, 'Spam comment');
    expect(fn () => $service->reportComment(7, 10, 20, 30, 'Spam comment'))->toThrow(DomainException::class);
});
