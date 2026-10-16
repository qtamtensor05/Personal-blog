<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

// Bootstraps the first admin: there is no one yet who could grant the role via the UI.
Artisan::command('admin:grant {email}', function (string $email) {
    $user = DB::table('users')->where('email', $email)->whereNull('deleted_at')->first();

    if ($user === null || $user->status !== 'ACTIVE') {
        $this->error('No active user with that email.');

        return 1;
    }

    $roleId = DB::table('platform_roles')->where('code', 'SUPER_ADMIN')->value('id');

    if ($roleId === null) {
        $this->error('SUPER_ADMIN role missing. Run: php artisan db:seed --class=AccessControlSeeder');

        return 1;
    }

    DB::table('user_platform_roles')->insertOrIgnore([
        'user_id' => $user->id,
        'role_id' => $roleId,
        'granted_by' => $user->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('security_audit_logs')->insert([
        'actor_user_id' => null,
        'subject_user_id' => $user->id,
        'event' => 'admin.role_granted_via_cli',
        'outcome' => 'SUCCESS',
        'context' => json_encode(['role' => 'SUPER_ADMIN']),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->info("Granted SUPER_ADMIN to {$email}.");

    return 0;
})->purpose('Grant the SUPER_ADMIN platform role to an active user');

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
