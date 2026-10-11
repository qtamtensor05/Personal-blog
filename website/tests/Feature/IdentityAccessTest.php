<?php

use App\Filament\Auth\PlatformLogin;
use App\Filament\Pages\AccessManagement;
use App\Models\User;
use App\Modules\AccessControl\Actions\ManageAccessAction;
use App\Modules\Identity\Actions\UpdateAccountAction;
use App\Modules\Moderation\Actions\ModerateAction;
use Database\Seeders\PlatformSeeder;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Facades\Filament;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;

function identityUser(): User
{
    return User::factory()->create(['status' => 'ACTIVE', 'email_verified_at' => now(), 'password' => Hash::make('CurrentPassword123')]);
}

function identitySession(User $user): void
{
    DB::table('sessions')->insert(['id' => 'old-session-'.$user->id, 'user_id' => $user->id, 'payload' => '', 'last_activity' => time()]);
}

it('updates only the authenticated profile and does not accept privilege fields', function () {
    $user = identityUser();
    $other = identityUser();
    $this->actingAs($user)->get('/account')->assertOk()->assertSee('Đổi mật khẩu');
    $this->post('/account/profile', ['name' => 'Tên mới', 'user_id' => $other->id, 'status' => 'DISABLED'])->assertRedirect('/account');
    expect($user->fresh()->name)->toBe('Tên mới')->and($other->fresh()->name)->not->toBe('Tên mới')->and($user->fresh()->status)->toBe('ACTIVE');
    $this->post('/account/password', ['current_password' => 'wrong', 'password' => 'NewPassword123', 'password_confirmation' => 'NewPassword123'])->assertSessionHasErrors('current_password');
});

it('changes password with reauthentication and revokes sessions and reset tokens', function () {
    $user = identityUser();
    identitySession($user);
    Password::createToken($user);
    $this->actingAs($user)->post('/account/password', ['current_password' => 'CurrentPassword123', 'password' => 'NewPassword123', 'password_confirmation' => 'NewPassword123'])->assertRedirect('/login');
    $this->assertGuest();
    expect(Hash::check('NewPassword123', $user->fresh()->password))->toBeTrue();
    $this->assertDatabaseMissing('sessions', ['user_id' => $user->id]);
    $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);
    $log = DB::table('admin_audit_logs')->where('action', 'identity.password.updated')->first();
    expect($log)->not->toBeNull()->and($log->after_data)->not->toContain('Password');
});

it('changes email only after password confirmation and invalidates old verification links', function () {
    Notification::fake();
    $user = identityUser();
    $oldLink = URL::temporarySignedRoute('verification.verify', now()->addMinutes(10), ['id' => $user->id, 'hash' => sha1($user->email)]);
    identitySession($user);
    $this->actingAs($user)->withSession(['platform_authenticated_user' => $user->id])->post('/account/email', ['email' => 'new@example.com', 'current_password' => 'CurrentPassword123'])->assertRedirect('/email/verify')->assertSessionMissing('platform_authenticated_user');
    expect($user->fresh()->status)->toBe('PENDING_VERIFICATION')->and($user->fresh()->email_verified_at)->toBeNull();
    Notification::assertSentTo($user->fresh(), VerifyEmail::class);
    $this->assertDatabaseMissing('sessions', ['id' => 'old-session-'.$user->id]);
    $this->get('/workspace')->assertRedirect('/email/verify');
    $this->get($oldLink)->assertForbidden();
    $newLink = URL::temporarySignedRoute('verification.verify', now()->addMinutes(10), ['id' => $user->id, 'hash' => sha1('new@example.com')]);
    $this->get($newLink)->assertRedirect('/workspace');
    expect($user->fresh()->canAccessApplication())->toBeTrue();
});

it('rejects duplicate emails and forbidden account updates', function () {
    $user = identityUser();
    $other = identityUser();
    $this->actingAs($user)->post('/account/email', ['email' => $other->email, 'current_password' => 'CurrentPassword123'])->assertSessionHasErrors('email');
    $user->forceFill(['locked_until' => now()->addHour()])->save();
    $this->post('/account/profile', ['name' => 'blocked'])->assertForbidden();
});

it('excludes deleted users from credential session remember and password broker lookups', function () {
    $user = identityUser();
    $user->forceFill(['deleted_at' => now(), 'remember_token' => 'remember-value'])->save();
    $provider = Auth::createUserProvider('users');
    expect($provider->retrieveById($user->id))->toBeNull()
        ->and($provider->retrieveByCredentials(['email' => $user->email]))->toBeNull()
        ->and($provider->retrieveByToken($user->id, 'remember-value'))->toBeNull();
    expect(Password::sendResetLink(['email' => $user->email]))->toBe(Password::InvalidUser);
    $this->post('/login', ['email' => $user->email, 'password' => 'CurrentPassword123'])->assertSessionHasErrors('email');
    $this->assertGuest();
});

it('rejects invalid expired and reused reset tokens and revokes sessions on success', function () {
    $user = identityUser();
    $payload = ['email' => $user->email, 'password' => 'ResetPassword123', 'password_confirmation' => 'ResetPassword123'];
    $this->post('/reset-password', $payload + ['token' => 'invalid'])->assertSessionHasErrors('email');
    $token = Password::createToken($user);
    DB::table('password_reset_tokens')->where('email', $user->email)->update(['created_at' => now()->subHours(2)]);
    $this->post('/reset-password', $payload + ['token' => $token])->assertSessionHasErrors('email');
    $token = Password::createToken($user);
    identitySession($user);
    $this->post('/reset-password', $payload + ['token' => $token])->assertRedirect('/login');
    $this->assertDatabaseMissing('sessions', ['user_id' => $user->id]);
    expect(Hash::check('ResetPassword123', $user->fresh()->password))->toBeTrue();
    $this->post('/reset-password', $payload + ['token' => $token])->assertSessionHasErrors('email');
});

it('validates app codes and consumes a recovery code only once without storing plaintext', function () {
    $user = identityUser();
    $provider = AppAuthentication::make()->recoverable();
    $provider->saveSecret($user, $provider->generateSecret());
    expect($provider->verifyCode($provider->getCurrentCode($user), $provider->getSecret($user)))->toBeTrue()
        ->and($provider->verifyCode('invalid', $provider->getSecret($user)))->toBeFalse();
    $codes = $provider->generateRecoveryCodes();
    $provider->saveRecoveryCodes($user, $codes);
    expect($user->fresh()->getAppAuthenticationRecoveryCodes()[0])->not->toBe($codes[0]);
    expect($provider->verifyRecoveryCode($codes[0], $user))->toBeTrue()
        ->and($provider->verifyRecoveryCode($codes[0], $user))->toBeFalse();
});

it('manages custom roles and grants with permission checks audit and session revocation', function () {
    $this->seed(PlatformSeeder::class);
    $super = identityUser();
    $target = identityUser();
    $systemId = DB::table('platform_roles')->where('code', 'SUPER_ADMIN')->value('id');
    DB::table('user_platform_roles')->insert(['user_id' => $super->id, 'role_id' => $systemId, 'granted_by' => $super->id]);
    $action = app(ManageAccessAction::class);
    $permission = DB::table('permissions')->where('code', 'platform.dashboard.view')->value('id');
    $action->execute($super, 'save', ['name' => 'Hỗ trợ', 'permissions' => [$permission]]);
    $role = DB::table('platform_roles')->where('name', 'Hỗ trợ')->value('id');
    identitySession($target);
    $action->execute($super, 'grant', ['user_id' => $target->id, 'role_id' => $role]);
    expect($target->hasPlatformPermission('platform.dashboard.view'))->toBeTrue();
    $this->assertDatabaseMissing('sessions', ['user_id' => $target->id]);
    expect(fn () => $action->execute($target, 'grant', ['user_id' => $super->id, 'role_id' => $role]))->toThrow(HttpException::class);
    expect(fn () => $action->execute($super, 'delete', ['role_id' => $role]))->toThrow(ValidationException::class);
    $action->execute($super, 'revoke', ['user_id' => $target->id, 'role_id' => $role]);
    $action->execute($super, 'save', ['role_id' => $role, 'name' => 'Tên mới', 'permissions' => []]);
    $action->execute($super, 'delete', ['role_id' => $role]);
    $this->assertDatabaseMissing('platform_roles', ['id' => $role]);
    expect(fn () => $action->execute($super, 'delete', ['role_id' => $systemId]))->toThrow(ValidationException::class);
    expect(fn () => $action->execute($super, 'revoke', ['user_id' => $super->id, 'role_id' => $systemId]))->toThrow(ValidationException::class, 'Super Admin');
    expect(fn () => app(UpdateAccountAction::class)->execute($super, 'email', ['email' => 'last@example.com', 'current_password' => 'CurrentPassword123']))->toThrow(ValidationException::class, 'Super Admin');
    expect(DB::table('admin_audit_logs')->where('action', 'access.admin.grant')->exists())->toBeTrue();
});

it('renders access management for authorized admins only and saves through Livewire', function () {
    $this->seed(PlatformSeeder::class);
    $super = identityUser();
    $role = DB::table('platform_roles')->where('code', 'SUPER_ADMIN')->value('id');
    DB::table('user_platform_roles')->insert(['user_id' => $super->id, 'role_id' => $role, 'granted_by' => $super->id]);
    Filament::setCurrentPanel(Filament::getPanel('platform'));
    $this->actingAs($super);
    Livewire::test(AccessManagement::class)->assertSee('Vai trò')->set('form.name', 'Vai trò thử')->call('mutate', 'save')->assertHasNoErrors()->assertSee('Vai trò thử');
    $this->actingAs(identityUser());
    Livewire::test(AccessManagement::class)->assertForbidden();
});

it('requires an app challenge before establishing a platform session', function () {
    $this->seed(PlatformSeeder::class);
    $super = identityUser();
    $role = DB::table('platform_roles')->where('code', 'SUPER_ADMIN')->value('id');
    DB::table('user_platform_roles')->insert(['user_id' => $super->id, 'role_id' => $role, 'granted_by' => $super->id]);
    Filament::setCurrentPanel(Filament::getPanel('platform'));
    $provider = AppAuthentication::make()->recoverable();
    $provider->saveSecret($super, $provider->generateSecret());
    $component = Livewire::test(PlatformLogin::class)->set('data.email', $super->email)->set('data.password', 'CurrentPassword123')->call('authenticate')->assertHasNoErrors();
    expect($component->get('userUndertakingMultiFactorAuthentication'))->not->toBeNull();
    expect(session('platform_authenticated_user'))->toBeNull();
    $this->assertGuest();
    $component->set('data.multiFactor.app.code', 'invalid')->call('authenticate')->assertHasErrors();
    $this->assertGuest();
    $component->set('data.multiFactor.app.code', $provider->getCurrentCode($super))->call('authenticate')->assertHasNoErrors();
    $this->assertAuthenticatedAs($super);
    expect(session('platform_authenticated_user'))->toBe($super->id);
});

it('does not revive an old remember cookie after an account restriction is lifted', function () {
    $this->seed(PlatformSeeder::class);
    $admin = identityUser();
    $target = identityUser();
    $target->forceFill(['remember_token' => 'old-remember-cookie'])->save();
    identitySession($target);
    $role = DB::table('platform_roles')->where('code', 'PLATFORM_ADMIN')->value('id');
    DB::table('user_platform_roles')->insert(['user_id' => $admin->id, 'role_id' => $role, 'granted_by' => $admin->id]);
    $action = app(ModerateAction::class);
    $action->execute($admin, 'users', $target->id, ['status' => 'SUSPENDED', 'reason' => 'Kiểm tra hạn chế tài khoản']);
    $this->assertDatabaseMissing('sessions', ['user_id' => $target->id]);
    $action->execute($admin, 'users', $target->id, ['status' => 'ACTIVE', 'reason' => 'Đã kiểm tra, khôi phục tài khoản']);
    expect($target->fresh()->canAccessApplication())->toBeTrue()
        ->and(Auth::createUserProvider('users')->retrieveByToken($target->id, 'old-remember-cookie'))->toBeNull();
    $this->assertDatabaseHas('account_restrictions', ['user_id' => $target->id, 'status' => 'REVOKED']);
});
