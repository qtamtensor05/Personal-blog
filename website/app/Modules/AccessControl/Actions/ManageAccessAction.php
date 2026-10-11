<?php

namespace App\Modules\AccessControl\Actions;

use App\Models\User;
use App\Modules\AccessControl\LastSuperAdminGuard;
use App\Shared\Contracts\AuditRecorder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final class ManageAccessAction
{
    public function execute(User $actor, string $operation, array $input): void
    {
        abort_unless(in_array($operation, ['save', 'delete', 'grant', 'revoke'], true), 404);
        DB::transaction(function () use ($actor, $operation, $input): void {
            // Stable lock serializes role/grant mutations, including the last-admin check.
            $roles = DB::table('platform_roles')->orderBy('id')->lockForUpdate()->get();
            $actor = User::query()->findOrFail($actor->id);
            $permission = in_array($operation, ['grant', 'revoke']) ? 'platform.admins.manage' : 'platform.roles.manage';
            abort_unless($actor->canAccessApplication() && $actor->hasPlatformPermission($permission), 403);
            if (in_array($operation, ['save', 'delete'])) {
                $data = Validator::make($input, ['role_id' => 'nullable|integer', 'name' => $operation === 'save' ? 'required|string|max:160' : 'nullable', 'permissions' => 'array', 'permissions.*' => ['integer', Rule::exists('permissions', 'id')]])->validate();
                $role = $roles->firstWhere('id', $data['role_id'] ?? null);
                abort_if(! empty($data['role_id']) && ! $role, 404);
                if ($role && in_array($role->code, ['SUPER_ADMIN', 'PLATFORM_ADMIN'])) {
                    throw ValidationException::withMessages(['role' => 'Vai trò hệ thống chỉ đọc, không được sửa hoặc xóa.']);
                }
                if ($operation === 'delete') {
                    abort_unless($role, 404);
                    if (DB::table('user_platform_roles')->where('role_id', $role->id)->exists()) {
                        throw ValidationException::withMessages(['role' => 'Thu hồi các lượt gán trước khi xóa vai trò.']);
                    }
                    DB::table('platform_role_permissions')->where('role_id', $role->id)->delete();
                    DB::table('platform_roles')->where('id', $role->id)->delete();
                    $id = $role->id;
                } else {
                    $permissions = array_unique($data['permissions'] ?? []);
                    foreach (DB::table('permissions')->whereIn('id', $permissions)->pluck('code') as $code) {
                        abort_unless($actor->hasPlatformPermission($code), 403);
                    }
                    $id = $role?->id ?? DB::table('platform_roles')->insertGetId(['code' => 'CUSTOM_'.strtoupper(bin2hex(random_bytes(8))), 'name' => $data['name'], 'created_at' => now(), 'updated_at' => now()]);
                    DB::table('platform_roles')->where('id', $id)->update(['name' => $data['name'], 'updated_at' => now()]);
                    DB::table('platform_role_permissions')->where('role_id', $id)->delete();
                    foreach ($permissions as $permissionId) {
                        DB::table('platform_role_permissions')->insert(['role_id' => $id, 'permission_id' => $permissionId, 'created_at' => now(), 'updated_at' => now()]);
                    }
                    // Existing admin sessions must reauthenticate after a permission change.
                    $this->revokeSessions(DB::table('user_platform_roles')->where('role_id', $id)->pluck('user_id')->all());
                }
                app(AuditRecorder::class)->record($actor->id, 'access.role.'.$operation, 'platform_roles', $id);

                return;
            }
            $data = Validator::make($input, ['user_id' => 'required|integer|exists:users,id', 'role_id' => 'required|integer|exists:platform_roles,id'])->validate();
            $target = User::query()->whereKey($data['user_id'])->lockForUpdate()->firstOrFail();
            $role = $roles->firstWhere('id', $data['role_id']);
            abort_unless($role, 404);
            $grant = DB::table('user_platform_roles')->where('user_id', $target->id)->where('role_id', $role->id);
            if ($operation === 'revoke' && $role->code === 'SUPER_ADMIN') {
                app(LastSuperAdminGuard::class)->assertCanDeactivate($target->id);
            }
            if ($target->id === $actor->id) {
                throw ValidationException::withMessages(['access' => 'Không tự thay đổi quyền của chính mình.']);
            }
            if ($operation === 'grant') {
                abort_unless($target->canAccessApplication(), 422, 'Chỉ gán vai trò cho tài khoản ACTIVE đã xác minh và không bị khóa.');
                foreach (DB::table('platform_role_permissions as rp')->join('permissions as p', 'p.id', '=', 'rp.permission_id')->where('rp.role_id', $role->id)->pluck('p.code') as $code) {
                    abort_unless($actor->hasPlatformPermission($code), 403);
                }
                $grant->updateOrInsert(['user_id' => $target->id, 'role_id' => $role->id], ['granted_by' => $actor->id, 'expires_at' => null, 'created_at' => now(), 'updated_at' => now()]);
            } else {
                $grant->delete();
            }
            $this->revokeSessions([$target->id]);
            app(AuditRecorder::class)->record($actor->id, 'access.admin.'.$operation, 'users', $target->id, after: ['role_id' => $role->id]);
        });
    }

    private function revokeSessions(array $ids): void
    {
        DB::table('sessions')->whereIn('user_id', $ids)->delete();
        DB::table('users')->whereIn('id', $ids)->update(['remember_token' => null]);
    }
}
