<?php

namespace Database\Seeders;

use App\Domain\AccessControl\Enums\PlatformPermission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Idempotent: syncs permission codes from PlatformPermission and
 * a single SUPER_ADMIN role that holds all of them.
 */
class AccessControlSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        DB::transaction(function () use ($now): void {
            foreach (PlatformPermission::cases() as $permission) {
                DB::table('permissions')->upsert(
                    [
                        'code' => $permission->value,
                        'scope' => 'PLATFORM',
                        'description' => $permission->description(),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ],
                    ['code'],
                    ['description', 'updated_at'],
                );
            }

            DB::table('platform_roles')->upsert(
                [
                    'code' => 'SUPER_ADMIN',
                    'name' => 'Super Admin',
                    'description' => 'Full access to the Admin Console.',
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                ['code'],
                ['name', 'description', 'updated_at'],
            );

            $roleId = DB::table('platform_roles')->where('code', 'SUPER_ADMIN')->value('id');

            $rows = DB::table('permissions')
                ->pluck('id')
                ->map(
                    fn ($permissionId) => [
                        'role_id' => $roleId,
                        'permission_id' => $permissionId,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ],
                )
                ->all();

            DB::table('platform_role_permissions')->insertOrIgnore($rows);
        });
    }
}
