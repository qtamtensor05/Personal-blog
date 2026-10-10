<?php

use App\Modules\Content\Actions\PublishScheduledContentAction;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('platform:bootstrap-admin {email?}', function (): int {
    $email = $this->argument('email') ?: $this->ask('Email Super Admin');
    $name = $this->ask('Tên hiển thị', 'Super Admin');
    $password = env('BOOTSTRAP_ADMIN_PASSWORD') ?: $this->secret('Mật khẩu');
    if (! is_string($password) || strlen($password) < 12) {
        $this->error('Mật khẩu phải có ít nhất 12 ký tự.');

        return self::FAILURE;
    }
    DB::transaction(function () use ($email, $name, $password): void {
        $userId = DB::table('users')->insertGetId([
            'name' => $name,
            'email' => $email,
            'email_verified_at' => now(),
            'password' => Hash::make($password),
            'status' => 'ACTIVE',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $roleId = DB::table('platform_roles')->where('code', 'SUPER_ADMIN')->value('id');
        DB::table('user_platform_roles')->insert([
            'user_id' => $userId,
            'role_id' => $roleId,
            'granted_by' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    });
    $this->info('Đã tạo Super Admin. Hãy đăng nhập và hoàn tất 2FA trước khi vận hành production.');

    return self::SUCCESS;
})->purpose('Bootstrap the first Super Admin without storing credentials');

Schedule::call(
    fn () => app(PublishScheduledContentAction::class)->execute(),
)
    ->name('publish-scheduled-content')
    ->everyMinute()
    ->withoutOverlapping();
