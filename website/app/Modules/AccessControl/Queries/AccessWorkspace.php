<?php

namespace App\Modules\AccessControl\Queries;

use App\Models\User;
use Illuminate\Support\Facades\DB;

final class AccessWorkspace
{
    public function get(User $actor, string $search): array
    {
        abort_unless($actor->canAccessApplication() && ($actor->hasPlatformPermission('platform.roles.manage') || $actor->hasPlatformPermission('platform.admins.manage')), 403);

        return [
            'roles' => DB::table('platform_roles')->orderBy('id')->get(),
            'permissions' => DB::table('permissions')->orderBy('code')->get(['id', 'code', 'description']),
            'users' => DB::table('users')->whereNull('deleted_at')->when($search !== '', fn ($q) => $q->where('email', 'like', '%'.$search.'%'))->orderBy('id')->limit(30)->get(['id', 'name', 'email', 'status']),
            'grants' => DB::table('user_platform_roles as g')->join('users as u', 'u.id', '=', 'g.user_id')->join('platform_roles as r', 'r.id', '=', 'g.role_id')->orderBy('g.id')->paginate(20, ['g.user_id', 'g.role_id', 'u.email', 'r.name', 'g.expires_at']),
        ];
    }

    public function rolePermissions(User $actor, int $id): array
    {
        abort_unless($actor->canAccessApplication() && $actor->hasPlatformPermission('platform.roles.manage'), 403);

        return DB::table('platform_role_permissions')->where('role_id', $id)->pluck('permission_id')->all();
    }
}
