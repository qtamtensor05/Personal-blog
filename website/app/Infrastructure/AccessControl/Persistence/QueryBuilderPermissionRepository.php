<?php

declare(strict_types=1);

namespace App\Infrastructure\AccessControl\Persistence;

use App\Application\AccessControl\Contracts\PermissionRepository;
use Illuminate\Support\Facades\DB;

final class QueryBuilderPermissionRepository implements PermissionRepository
{
    public function codesForUser(int $userId): array
    {
        return DB::table('user_platform_roles as upr')
            ->join('platform_role_permissions as prp', 'prp.role_id', '=', 'upr.role_id')
            ->join('permissions as p', 'p.id', '=', 'prp.permission_id')
            ->where('upr.user_id', $userId)
            ->where(function ($query): void {
                $query->whereNull('upr.expires_at')->orWhere('upr.expires_at', '>', now());
            })
            ->distinct()
            ->pluck('p.code')
            ->map(fn ($code) => (string) $code)
            ->values()
            ->all();
    }
}
