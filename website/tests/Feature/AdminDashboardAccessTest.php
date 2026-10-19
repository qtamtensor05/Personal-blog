<?php

use Illuminate\Auth\GenericUser;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;

uses(DatabaseTransactions::class);

function adminDashboardTestUser(int $id): GenericUser
{
    return new GenericUser((array) DB::table('users')->where('id', $id)->first());
}

function userIdWithAdminAccess(): int
{
    return (int) DB::table('users as u')
        ->join('user_platform_roles as upr', 'upr.user_id', '=', 'u.id')
        ->join('platform_role_permissions as prp', 'prp.role_id', '=', 'upr.role_id')
        ->join('permissions as p', 'p.id', '=', 'prp.permission_id')
        ->where('u.status', 'ACTIVE')
        ->where('p.code', 'admin.access')
        ->where(fn ($query) => $query->whereNull('upr.expires_at')->orWhere('upr.expires_at', '>', now()))
        ->value('u.id');
}

it('shows the admin console link and dashboard to an authorized admin', function (): void {
    $adminId = userIdWithAdminAccess();
    expect($adminId)->toBeGreaterThan(0);

    $this->actingAs(adminDashboardTestUser($adminId))
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertSee('Admin Console');

    $this->actingAs(adminDashboardTestUser($adminId))
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('Admin Console');

    $this->actingAs(adminDashboardTestUser($adminId))
        ->get(route('admin.templates.index'))
        ->assertOk()
        ->assertSee('Tạp chí tối giản');
});

it('hides and forbids the admin console for a normal user', function (): void {
    $userId = (int) DB::table('users as u')
        ->where('u.status', 'ACTIVE')
        ->whereNotExists(function ($query): void {
            $query->selectRaw('1')
                ->from('user_platform_roles as upr')
                ->join('platform_role_permissions as prp', 'prp.role_id', '=', 'upr.role_id')
                ->join('permissions as p', 'p.id', '=', 'prp.permission_id')
                ->whereColumn('upr.user_id', 'u.id')
                ->where('p.code', 'admin.access')
                ->where(fn ($roles) => $roles->whereNull('upr.expires_at')->orWhere('upr.expires_at', '>', now()));
        })
        ->value('u.id');
    expect($userId)->toBeGreaterThan(0);

    $this->actingAs(adminDashboardTestUser($userId))
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertDontSee('Admin Console');

    $this->actingAs(adminDashboardTestUser($userId))
        ->get(route('admin.dashboard'))
        ->assertForbidden();
});
