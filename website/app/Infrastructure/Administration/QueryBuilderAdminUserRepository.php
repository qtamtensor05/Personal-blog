<?php

declare(strict_types=1);

namespace App\Infrastructure\Administration;

use App\Application\Administration\Contracts\AdminUserRepository;
use App\Application\Administration\DTOs\AdminUserRecord;
use App\Domain\Identity\Enums\UserStatus;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;

final class QueryBuilderAdminUserRepository implements AdminUserRepository
{
    private const FORMAT = 'Y-m-d H:i:s';

    public function search(?string $query, ?UserStatus $status, int $page, int $perPage): array
    {
        $builder = DB::table('users')
            ->whereNull('deleted_at')
            ->when($query !== null && $query !== '', function ($q) use ($query): void {
                // Escape LIKE wildcards so the search term is matched literally.
                $term = '%'.addcslashes((string) $query, '%_\\').'%';
                $q->where(
                    fn ($w) => $w->where('email', 'like', $term)->orWhere('name', 'like', $term),
                );
            })
            ->when($status !== null, fn ($q) => $q->where('status', $status->value));

        $total = (clone $builder)->count();

        $items = $builder
            ->orderByDesc('created_at')
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->get()
            ->map(fn ($row) => $this->toRecord($row))
            ->all();

        return ['items' => $items, 'total' => $total];
    }

    public function find(int $id): ?AdminUserRecord
    {
        $row = DB::table('users')->where('id', $id)->whereNull('deleted_at')->first();

        if ($row === null) {
            return null;
        }

        $roles = DB::table('user_platform_roles as upr')
            ->join('platform_roles as r', 'r.id', '=', 'upr.role_id')
            ->where('upr.user_id', $id)
            ->orderBy('r.name')
            ->get(['upr.role_id', 'r.code', 'r.name', 'upr.expires_at'])
            ->map(
                fn ($r) => [
                    'role_id' => (int) $r->role_id,
                    'code' => $r->code,
                    'name' => $r->name,
                    'expires_at' => $r->expires_at,
                ],
            )
            ->all();

        $restrictions = DB::table('account_restrictions')
            ->where('user_id', $id)
            ->where('status', 'ACTIVE')
            ->orderByDesc('starts_at')
            ->get(['id', 'kind', 'reason', 'starts_at', 'ends_at'])
            ->map(
                fn ($r) => [
                    'id' => (int) $r->id,
                    'kind' => $r->kind,
                    'reason' => $r->reason,
                    'starts_at' => $r->starts_at,
                    'ends_at' => $r->ends_at,
                ],
            )
            ->all();

        return $this->toRecord($row, $roles, $restrictions);
    }

    public function roles(): array
    {
        return DB::table('platform_roles')
            ->orderBy('name')
            ->get(['id', 'code', 'name'])
            ->map(fn ($r) => ['id' => (int) $r->id, 'code' => $r->code, 'name' => $r->name])
            ->all();
    }

    public function permissions(): array
    {
        return DB::table('permissions')
            ->orderBy('code')
            ->get(['id', 'code', 'description'])
            ->map(
                fn ($permission) => [
                    'id' => (int) $permission->id,
                    'code' => (string) $permission->code,
                    'description' => $permission->description,
                ],
            )
            ->all();
    }

    public function permissionOverrides(int $userId): array
    {
        return DB::table('user_platform_permissions as upp')
            ->join('permissions as p', 'p.id', '=', 'upp.permission_id')
            ->where('upp.user_id', $userId)
            ->get(['upp.permission_id', 'p.code', 'upp.effect'])
            ->map(
                fn ($permission) => [
                    'permission_id' => (int) $permission->permission_id,
                    'code' => (string) $permission->code,
                    'effect' => (string) $permission->effect,
                ],
            )
            ->all();
    }

    public function roleExists(int $roleId): bool
    {
        return DB::table('platform_roles')->where('id', $roleId)->exists();
    }

    public function roleCode(int $roleId): ?string
    {
        $code = DB::table('platform_roles')->where('id', $roleId)->value('code');

        return $code === null ? null : (string) $code;
    }

    public function userHasRole(int $userId, string $roleCode): bool
    {
        return DB::table('user_platform_roles as upr')
            ->join('platform_roles as r', 'r.id', '=', 'upr.role_id')
            ->join('users as u', 'u.id', '=', 'upr.user_id')
            ->where('upr.user_id', $userId)
            ->where('r.code', $roleCode)
            ->where('u.status', 'ACTIVE')
            ->whereNull('u.deleted_at')
            ->where(function ($query): void {
                $query->whereNull('upr.expires_at')->orWhere('upr.expires_at', '>', now());
            })
            ->exists();
    }

    public function countActiveSuperAdmins(): int
    {
        return (int) DB::table('user_platform_roles as upr')
            ->join('platform_roles as r', 'r.id', '=', 'upr.role_id')
            ->join('users as u', 'u.id', '=', 'upr.user_id')
            ->where('r.code', 'SUPER_ADMIN')
            ->where('u.status', 'ACTIVE')
            ->whereNull('u.deleted_at')
            ->where(function ($query): void {
                $query->whereNull('upr.expires_at')->orWhere('upr.expires_at', '>', now());
            })
            ->distinct('upr.user_id')
            ->count('upr.user_id');
    }

    public function restrict(
        int $userId,
        string $kind,
        UserStatus $status,
        string $reason,
        int $issuedBy,
        ?DateTimeImmutable $endsAt,
    ): void {
        DB::transaction(function () use (
            $userId,
            $kind,
            $status,
            $reason,
            $issuedBy,
            $endsAt,
        ): void {
            $now = now();

            DB::table('account_restrictions')->insert([
                'user_id' => $userId,
                'kind' => $kind,
                'status' => 'ACTIVE',
                'reason' => $reason,
                'issued_by' => $issuedBy,
                'starts_at' => $now,
                'ends_at' => $endsAt?->format(self::FORMAT),
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('users')
                ->where('id', $userId)
                ->update([
                    'status' => $status->value,
                    'locked_until' => $kind === 'LOCK' ? $endsAt?->format(self::FORMAT) : null,
                    'remember_token' => null,
                    'updated_at' => $now,
                ]);

            // Sessions use the database driver: end every active session now.
            DB::table('sessions')->where('user_id', $userId)->delete();
        });
    }

    public function liftRestrictions(
        int $userId,
        UserStatus $status,
        int $revokedBy,
        string $reason,
    ): void {
        DB::transaction(function () use ($userId, $status, $revokedBy, $reason): void {
            $now = now();

            DB::table('account_restrictions')
                ->where('user_id', $userId)
                ->where('status', 'ACTIVE')
                ->update([
                    'status' => 'REVOKED',
                    'revoked_by' => $revokedBy,
                    'revoked_at' => $now,
                    'revoke_reason' => $reason,
                    'updated_at' => $now,
                ]);

            DB::table('users')
                ->where('id', $userId)
                ->update([
                    'status' => $status->value,
                    'locked_until' => null,
                    'updated_at' => $now,
                ]);
        });
    }

    public function grantRole(
        int $userId,
        int $roleId,
        int $grantedBy,
        ?DateTimeImmutable $expiresAt,
    ): void {
        $now = now();

        DB::table('user_platform_roles')->upsert(
            [
                'user_id' => $userId,
                'role_id' => $roleId,
                'granted_by' => $grantedBy,
                'expires_at' => $expiresAt?->format(self::FORMAT),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            ['user_id', 'role_id'],
            ['granted_by', 'expires_at', 'updated_at'],
        );
    }

    public function revokeRole(int $userId, int $roleId): void
    {
        DB::table('user_platform_roles')
            ->where('user_id', $userId)
            ->where('role_id', $roleId)
            ->delete();
    }

    public function permissionExists(int $permissionId): bool
    {
        return DB::table('permissions')->where('id', $permissionId)->exists();
    }

    public function permissionCode(int $permissionId): ?string
    {
        $code = DB::table('permissions')->where('id', $permissionId)->value('code');

        return $code === null ? null : (string) $code;
    }

    public function setPermissionOverride(
        int $userId,
        int $permissionId,
        string $effect,
        int $grantedBy,
    ): void {
        $now = now();

        DB::table('user_platform_permissions')->upsert(
            [
                [
                    'user_id' => $userId,
                    'permission_id' => $permissionId,
                    'effect' => $effect,
                    'granted_by' => $grantedBy,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            ],
            ['user_id', 'permission_id'],
            ['effect', 'granted_by', 'updated_at'],
        );
    }

    public function auditLogs(int $page, int $perPage): array
    {
        $builder = DB::table('admin_audit_logs as l')->join(
            'users as u',
            'u.id',
            '=',
            'l.actor_user_id',
        );

        $total = (clone $builder)->count();

        $items = $builder
            ->orderByDesc('l.id')
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->get([
                'l.id',
                'l.action',
                'l.resource_type',
                'l.resource_id',
                'l.before_data',
                'l.after_data',
                'l.ip_address',
                'l.created_at',
                'u.email as actor_email',
            ])
            ->all();

        return ['items' => $items, 'total' => $total];
    }

    /**
     * @param  list<array{role_id: int, code: string, name: string, expires_at: ?string}>  $roles
     * @param  list<array{id: int, kind: string, reason: string, starts_at: string, ends_at: ?string}>  $restrictions
     */
    private function toRecord(
        object $row,
        array $roles = [],
        array $restrictions = [],
    ): AdminUserRecord {
        return new AdminUserRecord(
            id: (int) $row->id,
            name: $row->name,
            email: $row->email,
            status: UserStatus::from($row->status),
            emailVerifiedAt: $row->email_verified_at,
            lastLoginAt: $row->last_login_at,
            createdAt: (string) $row->created_at,
            roles: $roles,
            activeRestrictions: $restrictions,
        );
    }
}
