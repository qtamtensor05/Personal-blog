<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(AccessControlSeeder::class);

        $email = config('bootstrap.admin_email')
            ?: env('BOOTSTRAP_ADMIN_EMAIL');

        $password = config('bootstrap.admin_password')
            ?: env('BOOTSTRAP_ADMIN_PASSWORD');

        if (! $email && ! $password) {
            $this->call(LifestyleEditorialTemplateSeeder::class);
            $this->command?->warn(
                'Skipping Owner account: bootstrap credentials are not configured.'
            );

            return;
        }

        if (
            ! is_string($email) ||
            ! filter_var($email, FILTER_VALIDATE_EMAIL) ||
            ! is_string($password) ||
            strlen($password) < 12
        ) {
            throw new RuntimeException(
                'Configure a valid BOOTSTRAP_ADMIN_EMAIL and a BOOTSTRAP_ADMIN_PASSWORD of at least 12 characters.'
            );
        }

        $now = now();

        DB::transaction(function () use ($email, $password, $now): void {
            $user = DB::table('users')
                ->where('email', $email)
                ->first();

            if ($user === null) {
                $userId = DB::table('users')->insertGetId([
                    'name' => 'Owner',
                    'email' => $email,
                    'password' => Hash::make($password),
                    'status' => 'ACTIVE',
                    'email_verified_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            } else {
                $userId = $user->id;
            }

            $roleId = DB::table('platform_roles')
                ->where('code', 'SUPER_ADMIN')
                ->value('id');

            if (! $roleId) {
                throw new RuntimeException(
                    'SUPER_ADMIN role was not created by AccessControlSeeder.'
                );
            }

            DB::table('user_platform_roles')->insertOrIgnore([
                'user_id' => $userId,
                'role_id' => $roleId,
                'granted_by' => $userId,
                'expires_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        });

        $this->command?->info('Owner account is ready.');
        $this->call(LifestyleEditorialTemplateSeeder::class);
    }
}
