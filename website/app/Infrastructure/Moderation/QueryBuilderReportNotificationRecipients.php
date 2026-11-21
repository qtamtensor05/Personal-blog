<?php

declare(strict_types=1);

namespace App\Infrastructure\Moderation;

use App\Application\Moderation\Contracts\ReportNotificationRecipients;
use App\Domain\AccessControl\Enums\PlatformPermission;
use Illuminate\Support\Facades\DB;

final readonly class QueryBuilderReportNotificationRecipients implements ReportNotificationRecipients
{
    public function userIds(): array
    {
        $users = DB::table('users as u')->where('u.status', 'ACTIVE')->whereNull('u.deleted_at');
        foreach ([PlatformPermission::AccessAdminConsole, PlatformPermission::ModerateContent] as $permission) {
            $users->whereExists(function ($query) use ($permission): void {
                $query->selectRaw('1')->from('permissions as p')->where('p.code', $permission->value)
                    ->whereNotExists(function ($deny): void {
                        $deny->selectRaw('1')->from('user_platform_permissions as denied')
                            ->whereColumn('denied.user_id', 'u.id')->whereColumn('denied.permission_id', 'p.id')->where('denied.effect', 'DENY');
                    })->where(function ($grants): void {
                        $grants->whereExists(function ($allow): void {
                            $allow->selectRaw('1')->from('user_platform_permissions as allowed')
                                ->whereColumn('allowed.user_id', 'u.id')->whereColumn('allowed.permission_id', 'p.id')->where('allowed.effect', 'ALLOW');
                        })->orWhereExists(function ($role): void {
                            $role->selectRaw('1')->from('user_platform_roles as upr')
                                ->join('platform_role_permissions as prp', 'prp.role_id', '=', 'upr.role_id')
                                ->whereColumn('upr.user_id', 'u.id')->whereColumn('prp.permission_id', 'p.id')
                                ->where(fn ($expiry) => $expiry->whereNull('upr.expires_at')->orWhere('upr.expires_at', '>', now()));
                        });
                    });
            });
        }

        return $users->orderBy('u.id')->pluck('u.id')->map(fn ($id) => (int) $id)->all();
    }
}
