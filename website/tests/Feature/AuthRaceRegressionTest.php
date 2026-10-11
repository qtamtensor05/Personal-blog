<?php

use App\Models\User;
use App\Modules\Identity\Actions\UpdateAccountAction;
use App\Modules\Identity\Auth\ActiveUserProvider;
use App\Modules\Identity\Auth\SessionStamp;
use Database\Seeders\PlatformSeeder;
use Filament\Facades\Filament;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use PragmaRX\Google2FA\Google2FA;

it('rejects an authenticated session restored after revocation on non database stores', function (string $driver) {
    config(['session.driver' => $driver]);
    $user = User::factory()->create(['status' => 'ACTIVE', 'email_verified_at' => now()]);
    $oldStamp = SessionStamp::value($user);
    $this->actingAs($user)->get('/account')->assertOk();
    DB::table('users')->where('id', $user->id)->update(['remember_token' => 'revoked-epoch']);
    $this->withSession(['identity_stamp' => $oldStamp])->get('/account')->assertRedirect('/login');
    $this->assertGuest();
})->with(['array', 'file']);

it('discards a loaded authenticated session when revocation occurs during the request', function () {
    Route::middleware('web')->get('/test-in-flight-revocation', function () {
        DB::table('users')->where('id', auth()->id())->update(['remember_token' => 'new-epoch']);

        return 'authenticated-response';
    });
    $user = User::factory()->create(['status' => 'ACTIVE']);
    $this->actingAs($user)->get('/test-in-flight-revocation')->assertRedirect('/login');
    $this->assertGuest();
    expect(session('identity_stamp'))->toBeNull();
});

it('deletes tokens through the configured active broker rather than a hardcoded table', function () {
    Schema::create('alternate_reset_tokens', function (Blueprint $table) {
        $table->temporary();
        $table->string('email')->primary();
        $table->string('token');
        $table->timestamp('created_at')->nullable();
    });
    try {
        config(['auth.passwords.alternate' => ['provider' => 'users', 'table' => 'alternate_reset_tokens', 'expire' => 60, 'throttle' => 60], 'auth.defaults.passwords' => 'alternate']);
        $user = User::factory()->create(['status' => 'ACTIVE', 'email_verified_at' => now(), 'password' => Hash::make('CurrentPassword123')]);
        $token = Password::createToken($user);
        expect(Password::tokenExists($user, $token))->toBeTrue();
        app(UpdateAccountAction::class)->execute($user, 'password', ['current_password' => 'CurrentPassword123', 'password' => 'NewPassword123', 'password_confirmation' => 'NewPassword123']);
        expect(Password::tokenExists($user->fresh(), $token))->toBeFalse();
        $this->assertDatabaseMissing('alternate_reset_tokens', ['email' => $user->email]);
    } finally {
        DB::statement('DROP TEMPORARY TABLE IF EXISTS alternate_reset_tokens');
    }
});

it('supports provider models whose auth table has no soft delete column', function () {
    Schema::create('plain_auth_users', function (Blueprint $table) {
        $table->temporary();
        $table->id();
        $table->string('email');
        $table->string('password');
        $table->string('remember_token')->nullable();
    });
    try {
        $id = DB::table('plain_auth_users')->insertGetId(['email' => 'plain@example.test', 'password' => Hash::make('password')]);
        $provider = new ActiveUserProvider(app('hash'), PlainAuthUser::class);
        expect($provider->retrieveById($id)->email)->toBe('plain@example.test');
    } finally {
        DB::statement('DROP TEMPORARY TABLE IF EXISTS plain_auth_users');
    }
});

it('lets management-only roles enter their authorized page without dashboard access', function () {
    $this->seed(PlatformSeeder::class);
    $user = User::factory()->create(['status' => 'ACTIVE']);
    $role = DB::table('platform_roles')->insertGetId(['name' => 'Access manager', 'code' => 'ACCESS_MANAGER']);
    $permission = DB::table('permissions')->where('code', 'platform.roles.manage')->value('id');
    DB::table('platform_role_permissions')->insert(['role_id' => $role, 'permission_id' => $permission]);
    DB::table('user_platform_roles')->insert(['user_id' => $user->id, 'role_id' => $role, 'granted_by' => $user->id]);
    $panel = Filament::getPanel('platform');
    $user->saveAppAuthenticationSecret(app(Google2FA::class)->generateSecretKey());
    Filament::setCurrentPanel($panel);
    expect($user->canAccessPanel($panel))->toBeTrue()->and($user->hasPlatformPermission('platform.dashboard.view'))->toBeFalse();
    $this->actingAs($user)->withSession(['platform_authenticated_user' => $user->id])->get('/platform-admin/access-management')->assertOk();
    $this->get('/platform-admin')->assertForbidden();
});

class PlainAuthUser extends Illuminate\Foundation\Auth\User
{
    protected $table = 'plain_auth_users';
}
