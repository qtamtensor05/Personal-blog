<?php

use App\Application\Administration\UseCases\ManageUsers\ManageUsersService;
use App\Application\Moderation\Contracts\ContentReportRepository;
use App\Application\Moderation\UseCases\ModerateContent\ModerateContentService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;

uses(DatabaseTransactions::class);

it('notifies a user about role and restriction changes', function (): void {
    $actorId = (int) DB::table('users')->where('status', 'ACTIVE')->orderBy('id')->value('id');
    $targetId = (int) DB::table('users')
        ->where('status', 'ACTIVE')
        ->where('id', '!=', $actorId)
        ->orderByDesc('id')
        ->value('id');
    $roleId = (int) DB::table('platform_roles as r')
        ->whereNotExists(
            fn ($query) => $query
                ->selectRaw('1')
                ->from('user_platform_roles as upr')
                ->whereColumn('upr.role_id', 'r.id')
                ->where('upr.user_id', $targetId),
        )
        ->value('r.id');

    expect($actorId)
        ->toBeGreaterThan(0)
        ->and($targetId)
        ->toBeGreaterThan(0)
        ->and($roleId)
        ->toBeGreaterThan(0);

    $service = app(ManageUsersService::class);
    $service->grantRole($actorId, $targetId, $roleId, null);
    $service->restrict($actorId, $targetId, 'LOCK', 'Security review', null);

    expect(
        DB::table('notifications')
            ->where('recipient_user_id', $targetId)
            ->where('type', 'role.granted')
            ->exists(),
    )
        ->toBeTrue()
        ->and(
            DB::table('notifications')
                ->where('recipient_user_id', $targetId)
                ->where('type', 'account.restricted')
                ->exists(),
        )
        ->toBeTrue();
});

it('notifies the reporter when moderation review starts', function (): void {
    $blog = DB::table('blogs')
        ->whereNull('deleted_at')
        ->first(['id', 'owner_user_id']);
    expect($blog)->not->toBeNull();

    $reporterId = (int) DB::table('users')
        ->where('id', '!=', $blog->owner_user_id)
        ->whereNull('deleted_at')
        ->value('id');
    $adminId = (int) DB::table('users')
        ->where('id', '!=', $reporterId)
        ->whereNull('deleted_at')
        ->value('id');
    expect($reporterId)->toBeGreaterThan(0)->and($adminId)->toBeGreaterThan(0);

    $reportId = app(ContentReportRepository::class)->create(
        (int) $blog->id,
        null,
        $reporterId,
        'Integration test report',
    );
    app(ModerateContentService::class)->startReview($adminId, $reportId);

    expect(
        DB::table('notifications')
            ->where('recipient_user_id', $reporterId)
            ->where('type', 'moderation.review_started')
            ->whereJsonContains('data->report_id', $reportId)
            ->exists(),
    )->toBeTrue();
});
